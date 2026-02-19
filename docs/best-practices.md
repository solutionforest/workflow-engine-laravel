# Best Practices

## Workflow Design Principles

### Keep Actions Small and Focused

Each action should have a single responsibility:

```php
// Bad - Action does too many things
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

    // ...
}

// Good - Break into focused actions
$workflow = WorkflowBuilder::create('order-processing')
    ->addStep('validate', ValidateOrderAction::class)
    ->addStep('payment', ProcessPaymentAction::class)
    ->addStep('inventory', UpdateInventoryAction::class)
    ->email('confirmation', '{{ order.customer.email }}', 'Order Confirmed')
    ->build();
```

### Use Meaningful Names

Choose descriptive names for workflows and steps:

```php
// Bad - Unclear names
$workflow = WorkflowBuilder::create('flow1')
    ->addStep('step1', Action1::class)
    ->addStep('step2', Action2::class)
    ->build();

// Good - Clear, descriptive names
$workflow = WorkflowBuilder::create('user-onboarding')
    ->addStep('create-profile', CreateUserProfileAction::class)
    ->addStep('send-welcome-email', SendWelcomeEmailAction::class)
    ->addStep('assign-default-permissions', AssignPermissionsAction::class)
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
            // Temporary error - will be retried by the engine
            return ActionResult::failure('Payment processor unavailable', [
                'error_type' => 'temporary',
                'original_error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Unexpected error - log and fail
            Log::error('Unexpected payment error', [
                'order_id' => $context->getData('order.id'),
                'error' => $e->getMessage()
            ]);

            return ActionResult::failure('Payment processing failed');
        }
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order');
    }

    public function getName(): string
    {
        return 'Process Payment';
    }

    public function getDescription(): string
    {
        return 'Processes customer payment through configured gateway';
    }
}
```

## Performance Optimization

### Use Appropriate Queue Connections

Choose the right queue connection for your workload:

```php
// config/workflow-engine.php
return [
    'queue' => [
        'enabled' => true,
        'connection' => 'redis',
        'queue_name' => 'workflows',
    ],
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

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('recipients') && $context->hasData('template');
    }

    public function getName(): string
    {
        return 'Send Bulk Emails';
    }

    public function getDescription(): string
    {
        return 'Sends emails in batches to multiple recipients';
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

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order_id');
    }

    public function getName(): string
    {
        return 'Process Order';
    }

    public function getDescription(): string
    {
        return 'Processes an order by loading and validating it';
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
                'errors' => $validator->errors()->toArray()
            ]);
        }

        // Process validated data...

        return ActionResult::success();
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('user_id') && $context->hasData('amount');
    }

    public function getName(): string
    {
        return 'Secure Data Validation';
    }

    public function getDescription(): string
    {
        return 'Validates and processes input data securely';
    }
}
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

    $definition = WorkflowBuilder::create($workflowName)
        // ... build workflow
        ->build();

    $engine = app(WorkflowEngine::class);
    $instanceId = $engine->start($workflowName, $definition->toArray(), $request->validated());

    return response()->json(['instance_id' => $instanceId]);
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
            'step_id' => $context->stepId,
            'data_keys' => array_keys($context->getData())
        ]);

        $startTime = microtime(true);

        try {
            $result = $this->performAction($context);

            Log::info('Action completed', [
                'workflow_id' => $context->workflowId,
                'step_id' => $context->stepId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success' => $result->isSuccess()
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Action failed', [
                'workflow_id' => $context->workflowId,
                'step_id' => $context->stepId,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'Logging Action';
    }

    public function getDescription(): string
    {
        return 'Wraps action execution with comprehensive logging';
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
            $instance = $event->instance;
            $state = $instance->getState();

            // Alert if critical workflow fails
            if ($state->isError()) {
                Alert::critical("Workflow failed: {$instance->getName()}", [
                    'workflow_id' => $instance->getId(),
                    'error' => $event->exception->getMessage(),
                ]);
            }
        }
    ]
];
```

## Testing Strategies

### Use WorkflowContext in Tests

Create consistent test contexts:

```php
class WorkflowTest extends TestCase
{
    public function test_order_processing_action()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $context = new WorkflowContext(
            workflowId: 'test-workflow-1',
            stepId: 'process-order',
            data: ['order' => $order->toArray()]
        );

        $action = new ProcessOrderAction();
        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());
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

        $context = new WorkflowContext(
            workflowId: 'test-1',
            stepId: 'api-call',
            data: ['order_id' => 123]
        );

        $action = new ExternalApiAction();
        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());

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

        $context = new WorkflowContext(
            workflowId: 'test-1',
            stepId: 'payment',
            data: ['payment' => ['amount' => 100]]
        );

        $action = new ProcessPaymentAction();
        $result = $action->execute($context);

        $this->assertTrue($result->isFailure());
        $this->assertNotNull($result->getErrorMessage());
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
 *
 * Error handling:
 * - Payment failures trigger retry logic (3 attempts)
 * - Inventory shortages fail the workflow
 * - Shipping failures are logged for manual review
 *
 * @param array $data Must contain: order, customer, payment_method
 */
function createOrderProcessingWorkflow(): WorkflowDefinition
{
    return WorkflowBuilder::create('order-processing')
        ->description('E-commerce order processing workflow')
        ->addStep('validate-order', ValidateOrderAction::class)
        ->addStep('process-payment', ProcessPaymentAction::class, [], '2m', 3)
        ->addStep('update-inventory', UpdateInventoryAction::class)
        ->addStep('create-shipment', CreateShipmentAction::class)
        ->email('order-confirmation', '{{ customer.email }}', 'Order Confirmed')
        ->build();
}
```
