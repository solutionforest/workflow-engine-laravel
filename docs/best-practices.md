# Best Practices

## Workflow Design Principles

### Keep Actions Small and Focused

Each action should have a single responsibility:

```php
// ❌ Bad - Action does too many things
class ProcessOrderAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');
        
        // Validate order
        if (!$this->validateOrder($order)) {
            return ActionResult::failure('Invalid order');
        }
        
        // Process payment
        $payment = $this->processPayment($order);
        
        // Update inventory
        $this->updateInventory($order);
        
        // Send email
        $this->sendConfirmationEmail($order);
        
        return ActionResult::success();
    }
}

// ✅ Good - Break into focused actions
$workflow = WorkflowBuilder::create('order-processing')
    ->step('validate', ValidateOrderAction::class)
    ->step('payment', ProcessPaymentAction::class)
    ->step('inventory', UpdateInventoryAction::class)
    ->email('confirmation', to: '{{ order.customer.email }}')
    ->build();
```

### Use Meaningful Names

Choose descriptive names for workflows and steps:

```php
// ❌ Bad - Unclear names
$workflow = WorkflowBuilder::create('flow1')
    ->step('step1', Action1::class)
    ->step('step2', Action2::class)
    ->build();

// ✅ Good - Clear, descriptive names
$workflow = WorkflowBuilder::create('user-onboarding')
    ->step('create-profile', CreateUserProfileAction::class)
    ->step('send-welcome-email', SendWelcomeEmailAction::class)
    ->step('assign-default-permissions', AssignPermissionsAction::class)
    ->build();
```

### Handle Errors Gracefully

Always consider what happens when things go wrong:

```php
class PaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        try {
            $payment = $this->processPayment($context->getData('order'));
            
            return ActionResult::success([
                'payment_id' => $payment->id,
                'status' => 'completed'
            ]);
        } catch (InsufficientFundsException $e) {
            // Expected error - customer needs to update payment method
            return ActionResult::failure('Insufficient funds', [
                'error_type' => 'insufficient_funds',
                'retry_possible' => true
            ]);
        } catch (PaymentProcessorException $e) {
            // Temporary error - can retry
            return ActionResult::retry('Payment processor unavailable');
        } catch (\Exception $e) {
            // Unexpected error - log and fail
            Log::error('Unexpected payment error', [
                'order_id' => $context->getData('order.id'),
                'error' => $e->getMessage()
            ]);
            
            return ActionResult::failure('Payment processing failed');
        }
    }
}
```

## Performance Optimization

### Use Appropriate Queue Connections

Choose the right queue connection for your workload:

```php
// config/workflow-engine.php
return [
    'workflows' => [
        'user-onboarding' => [
            'queue' => 'high-priority',
            'connection' => 'redis'
        ],
        'data-export' => [
            'queue' => 'low-priority',
            'connection' => 'database'
        ],
        'real-time-notifications' => [
            'queue' => 'sync', // Run immediately
            'connection' => 'sync'
        ]
    ]
];
```

### Batch Operations

Process multiple items efficiently:

```php
class BulkEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $recipients = $context->getData('recipients');
        $template = $context->getData('template');
        
        // Process in batches of 100
        $batches = array_chunk($recipients, 100);
        
        foreach ($batches as $batch) {
            Mail::to($batch)->queue(new BulkEmail($template));
        }
        
        return ActionResult::success([
            'sent_count' => count($recipients),
            'batch_count' => count($batches)
        ]);
    }
}
```

### Lazy Load Data

Don't load data until you need it:

```php
class ProcessOrderAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $orderId = $context->getData('order_id');
        
        // Only load the order when we need it
        $order = Order::with(['items', 'customer'])->find($orderId);
        
        if (!$order) {
            return ActionResult::failure('Order not found');
        }
        
        // Process the order...
        
        return ActionResult::success();
    }
}
```

## Security Considerations

### Validate Input Data

Always validate data coming into workflows:

```php
class SecureAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        // Validate required fields
        $validator = Validator::make($data, [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|in:USD,EUR,GBP'
        ]);
        
        if ($validator->fails()) {
            return ActionResult::failure('Invalid input data', [
                'errors' => $validator->errors()
            ]);
        }
        
        // Process validated data...
        
        return ActionResult::success();
    }
}
```

### Sanitize Template Data

When using templates, sanitize the data:

```php
$workflow = WorkflowBuilder::create('secure-email')
    ->email('notification', [
        'to' => '{{ user.email }}',
        'subject' => 'Welcome {{ user.name|escape }}', // Escape user input
        'data' => [
            'username' => Str::limit($user->name, 50), // Limit length
            'safe_content' => strip_tags($user->bio) // Remove HTML
        ]
    ])
    ->build();
```

### Limit Workflow Access

Use policies to control who can start workflows:

```php
class WorkflowPolicy
{
    public function start(User $user, string $workflowName): bool
    {
        return match($workflowName) {
            'admin-tasks' => $user->hasRole('admin'),
            'user-workflows' => $user->hasPermission('create-workflows'),
            'public-workflows' => true,
            default => false
        };
    }
}

// In your controller
public function startWorkflow(Request $request, string $workflowName)
{
    $this->authorize('start', [Workflow::class, $workflowName]);
    
    $workflow = WorkflowBuilder::create($workflowName)
        // ... build workflow
        ->build();
    
    return $workflow->start($request->validated());
}
```

## Monitoring and Debugging

### Add Comprehensive Logging

Log important events and state changes:

```php
class LoggingAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        Log::info('Starting action', [
            'workflow_id' => $context->workflowId,
            'step_id' => $context->currentStepId,
            'data_keys' => array_keys($context->getData())
        ]);
        
        $startTime = microtime(true);
        
        try {
            $result = $this->performAction($context);
            
            Log::info('Action completed', [
                'workflow_id' => $context->workflowId,
                'step_id' => $context->currentStepId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success' => $result->success
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Action failed', [
                'workflow_id' => $context->workflowId,
                'step_id' => $context->currentStepId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

### Use Meaningful Metrics

Track business metrics, not just technical ones:

```php
class MetricsCollectingAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');
        
        // Track business metrics
        Metrics::increment('orders.processed', 1, [
            'workflow' => $context->getWorkflowName(),
            'order_type' => $order['type'],
            'customer_tier' => $order['customer']['tier']
        ]);
        
        Metrics::histogram('order.value', $order['total'], [
            'currency' => $order['currency']
        ]);
        
        $result = $this->processOrder($order);
        
        if ($result->success) {
            Metrics::increment('orders.successful');
        } else {
            Metrics::increment('orders.failed', 1, [
                'failure_reason' => $result->message
            ]);
        }
        
        return $result;
    }
}
```

### Set Up Alerting

Alert on workflow failures and performance issues:

```php
// In your EventServiceProvider
use SolutionForest\WorkflowEngine\Events\WorkflowFailedEvent;

protected $listen = [
    WorkflowFailedEvent::class => [
        function (WorkflowFailedEvent $event) {
            // Alert if critical workflow fails
            if (in_array($event->workflowName, ['payment-processing', 'order-fulfillment'])) {
                Alert::critical("Critical workflow failed: {$event->workflowName}", [
                    'workflow_id' => $event->workflowId,
                    'error' => $event->error,
                    'step' => $event->failedStep
                ]);
            }
            
            // Alert if too many workflows are failing
            $recentFailures = WorkflowExecution::where('state', 'failed')
                ->where('created_at', '>', now()->subMinutes(15))
                ->count();
                
            if ($recentFailures > 10) {
                Alert::warning("High workflow failure rate: {$recentFailures} failures in 15 minutes");
            }
        }
    ]
];
```

## Testing Strategies

### Use Factories for Test Data

Create consistent test data:

```php
// database/factories/WorkflowContextFactory.php
class WorkflowContextFactory extends Factory
{
    public function definition()
    {
        return [
            'workflow_id' => $this->faker->uuid,
            'current_step_id' => 'test-step',
            'data' => [
                'user' => User::factory()->make()->toArray(),
                'order' => Order::factory()->make()->toArray()
            ]
        ];
    }
    
    public function withOrder(Order $order)
    {
        return $this->state(['data' => ['order' => $order->toArray()]]);
    }
}

// In your tests
class WorkflowTest extends TestCase
{
    public function test_order_processing_workflow()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $context = WorkflowContext::factory()->withOrder($order)->create();
        
        $action = new ProcessOrderAction();
        $result = $action->execute($context);
        
        $this->assertTrue($result->success);
    }
}
```

### Mock External Dependencies

Don't rely on external services in tests:

```php
class ExternalApiTest extends TestCase
{
    public function test_api_action_success()
    {
        // Mock HTTP responses
        Http::fake([
            'api.payment.com/*' => Http::response(['status' => 'success'], 200),
            'api.shipping.com/*' => Http::response(['tracking' => '123'], 200)
        ]);
        
        $context = WorkflowContext::factory()->create();
        $action = new ExternalApiAction();
        $result = $action->execute($context);
        
        $this->assertTrue($result->success);
        
        // Verify the right calls were made
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.payment.com');
        });
    }
}
```

### Test Error Scenarios

Don't just test the happy path:

```php
class ErrorHandlingTest extends TestCase
{
    public function test_payment_failure_handling()
    {
        // Mock a payment failure
        Http::fake([
            'api.payment.com/*' => Http::response(['error' => 'Card declined'], 402)
        ]);
        
        $context = WorkflowContext::factory()->create();
        $action = new ProcessPaymentAction();
        $result = $action->execute($context);
        
        $this->assertFalse($result->success);
        $this->assertEquals('Payment failed', $result->message);
        $this->assertArrayHasKey('retry_possible', $result->data);
    }
    
    public function test_network_timeout_handling()
    {
        Http::fake(function () {
            throw new ConnectException('Connection timeout', new Request('GET', 'test'));
        });
        
        $context = WorkflowContext::factory()->create();
        $action = new ExternalApiAction();
        $result = $action->execute($context);
        
        $this->assertEquals('retry', $result->status);
    }
}
```

## Documentation

### Document Workflow Purpose

Always document what your workflow does:

```php
/**
 * E-commerce Order Processing Workflow
 * 
 * This workflow handles the complete order processing lifecycle:
 * 1. Validates the order data and inventory
 * 2. Processes payment using the configured payment gateway
 * 3. Updates inventory levels
 * 4. Creates shipping label and arranges pickup
 * 5. Sends confirmation emails to customer
 * 6. Schedules follow-up communications
 * 
 * Error handling:
 * - Payment failures trigger retry logic (3 attempts)
 * - Inventory shortages cancel the order and notify customer
 * - Shipping failures are escalated to operations team
 * 
 * @param array $data Must contain: order, customer, payment_method
 * @return WorkflowInstance
 */
function createOrderProcessingWorkflow(array $data): WorkflowDefinition
{
    return WorkflowBuilder::create('order-processing')
        ->step('validate-order', ValidateOrderAction::class)
        ->step('process-payment', ProcessPaymentAction::class)
            ->retry(attempts: 3, backoff: 'exponential')
        ->step('update-inventory', UpdateInventoryAction::class)
        ->step('create-shipment', CreateShipmentAction::class)
        ->email('order-confirmation', to: '{{ customer.email }}')
        ->build();
}
```

### Maintain Change Logs

Keep track of workflow changes:

```php
/**
 * Order Processing Workflow - Change Log
 * 
 * v2.1.0 (2024-01-15)
 * - Added retry logic for payment processing
 * - Improved error handling for inventory shortages
 * - Added customer notification for shipping delays
 * 
 * v2.0.0 (2024-01-01)
 * - Migrated to new fluent API
 * - Added parallel processing for notifications
 * - Breaking: Changed context data structure
 * 
 * v1.5.0 (2023-12-01)
 * - Added support for international shipping
 * - Improved payment gateway integration
 */
```
