# Advanced Features

## PHP 8.3+ Attributes

Use declarative attributes to configure your workflow actions:

### WorkflowStep Attribute

Define step metadata directly on your action classes:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;

#[WorkflowStep(
    id: 'send_email',
    name: 'Send Welcome Email',
    description: 'Sends a welcome email to new users',
    config: ['template' => 'welcome', 'async' => true],
    required: true,
    order: 1
)]
class SendWelcomeEmailAction implements WorkflowAction
{
    // Action implementation...
}
```

### Timeout Attribute

Set execution timeouts for your actions:

```php
use SolutionForest\WorkflowEngine\Attributes\Timeout;

// Single time unit
#[Timeout(seconds: 30)]
#[Timeout(minutes: 5)]
#[Timeout(hours: 1)]

// Combined time units
#[Timeout(minutes: 5, seconds: 30)] // 5 minutes 30 seconds
class LongRunningAction implements WorkflowAction
{
    // Action will timeout after specified duration
}
```

### Retry Attribute

Configure automatic retry behavior:

```php
use SolutionForest\WorkflowEngine\Attributes\Retry;

// Basic retry configuration
#[Retry(attempts: 3)]

// With backoff strategy
#[Retry(attempts: 5, backoff: 'exponential')]
#[Retry(attempts: 3, backoff: 'linear', delay: 1000)]

// Maximum delay cap
#[Retry(attempts: 5, backoff: 'exponential', delay: 1000, maxDelay: 30000)]
class UnreliableApiAction implements WorkflowAction
{
    // Will automatically retry on failure
}
```

Available backoff strategies:
- `linear` - Fixed delay between retries
- `exponential` - Exponentially increasing delay  
- `fixed` - Same delay for all retries

### Condition Attribute

Add conditional execution rules:

```php
use SolutionForest\WorkflowEngine\Attributes\Condition;

// Single condition
#[Condition('user.email is not null')]

// Multiple conditions (repeatable attribute)
#[Condition('user.email is not null')]
#[Condition('order.amount > 100')]
#[Condition('user.premium = true', operator: 'or')]
class ConditionalAction implements WorkflowAction
{
    // Only executes if conditions are met
}
```

Condition expressions support:
- Property access: `user.email`, `order.amount`
- Comparisons: `>`, `<`, `>=`, `<=`, `=`, `!=`
- Null checks: `is null`, `is not null`
- Operators: `and`, `or`

### Combining Attributes

You can use multiple attributes together:

```php
#[WorkflowStep(
    id: 'process_payment',
    name: 'Process Payment',
    description: 'Processes customer payment'
)]
#[Timeout(minutes: 2)]
#[Retry(attempts: 3, backoff: 'exponential')]
#[Condition('order.amount > 0')]
#[Condition('payment.method is not null')]
class ProcessPaymentAction implements WorkflowAction
{
    // Robust payment processing with timeout, retries, and conditions
}
```

## Error Handling and Retries

### Automatic Retries

Configure automatic retries for unreliable operations:

```php
$workflow = WorkflowBuilder::create('robust-workflow')
    ->addStep('api-call', ApiCallAction::class, [], null, 3) // 3 retry attempts
    ->addStep('database-operation', DatabaseAction::class, [], null, 5) // 5 retry attempts  
    ->build();
```

### Backoff Strategies

- **Linear**: Fixed delay between retries
- **Exponential**: Increasing delay (1s, 2s, 4s, 8s...)
- **Custom**: Define your own backoff function

```php
// Custom backoff function
$workflow = WorkflowBuilder::create('custom-retry')
    ->addStep('operation', MyAction::class, [], null, 3) // Basic retry with 3 attempts
        ->retry(attempts: 3, backoff: function($attempt) {
            return $attempt * 1000; // 1s, 2s, 3s
        })
    ->build();
```

### Error Compensation

Handle failures with compensation actions:

```php
class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        try {
            $payment = $this->chargeCard($context->getData('payment'));
            return ActionResult::success(['payment_id' => $payment->id]);
        } catch (PaymentException $e) {
            // Trigger compensation workflow
            CompensationWorkflow::start([
                'original_context' => $context,
                'error' => $e->getMessage()
            ]);
            
            return ActionResult::failure('Payment failed: ' . $e->getMessage());
        }
    }
}
```

## Timeouts

### Step-Level Timeouts

Set timeouts for individual steps:

```php
$workflow = WorkflowBuilder::create('timed-workflow')
    ->step('quick-operation', QuickAction::class)
        ->timeout(seconds: 30)
    ->step('slow-operation', SlowAction::class)
        ->timeout(minutes: 5)
    ->build();
```

### Workflow-Level Timeouts

Set a timeout for the entire workflow:

```php
$workflow = WorkflowBuilder::create('deadline-workflow')
    ->globalTimeout(hours: 2)
    ->step('step1', ActionOne::class)
    ->step('step2', ActionTwo::class)
    ->build();
```

### Timeout Handling

Handle timeouts gracefully:

```php
class TimeSensitiveAction implements WorkflowAction
{
    #[Timeout(seconds: 30)]
    public function execute(WorkflowContext $context): ActionResult
    {
        $startTime = time();
        
        while (time() - $startTime < 25) { // Leave 5 seconds buffer
            if ($this->operationComplete()) {
                return ActionResult::success();
            }
            sleep(1);
        }
        
        return ActionResult::failure('Operation timed out');
    }
}
```

## Conditional Workflows

### Simple Conditions

Use simple expressions for conditions:

```php
$workflow = WorkflowBuilder::create('conditional-flow')
    ->when('user.type == "premium"', fn($builder) =>
        $builder->step('premium-benefits', PremiumBenefitsAction::class)
    )
    ->when('order.total > 100', fn($builder) =>
        $builder->step('apply-discount', DiscountAction::class)
    )
    ->build();
```

### Complex Conditions

Use complex logic with the ConditionAction:

```php
use SolutionForest\WorkflowEngine\Actions\ConditionAction;

$workflow = WorkflowBuilder::create('complex-conditions')
    ->step('evaluate', new ConditionAction([
        'user.age >= 18 AND user.verified == true' => [
            ['action' => VerifiedAdultAction::class],
        ],
        'user.age >= 18 AND user.verified == false' => [
            ['action' => RequestVerificationAction::class],
        ],
        'user.age < 18' => [
            ['action' => MinorUserAction::class],
        ],
    ]))
    ->build();
```

### Dynamic Conditions

Evaluate conditions at runtime:

```php
class DynamicConditionAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $user = $context->getData('user');
        
        if ($this->shouldSendWelcomeEmail($user)) {
            return ActionResult::success(['next_action' => 'send_welcome']);
        }
        
        if ($this->shouldRequestVerification($user)) {
            return ActionResult::success(['next_action' => 'request_verification']);
        }
        
        return ActionResult::success(['next_action' => 'skip']);
    }
}
```

## Parallel Execution

### Fork and Join

Execute multiple branches in parallel:

```php
$workflow = WorkflowBuilder::create('parallel-processing')
    ->fork([
        'email-branch' => fn($builder) => 
            $builder->email('notification', to: '{{ user.email }}'),
        'sms-branch' => fn($builder) =>
            $builder->step('send-sms', SendSmsAction::class),
        'push-branch' => fn($builder) =>
            $builder->step('push-notification', PushNotificationAction::class)
    ])
    ->join()
    ->step('cleanup', CleanupAction::class)
    ->build();
```

### Async Actions

Mark actions as asynchronous:

```php
use SolutionForest\WorkflowEngine\Attributes\Async;

class UploadFileAction implements WorkflowAction
{
    #[Async]
    public function execute(WorkflowContext $context): ActionResult
    {
        // This action will run in a separate queue job
        $file = $context->getData('file');
        $this->uploadToS3($file);
        
        return ActionResult::success(['upload_url' => $url]);
    }
}
```

## Queue Integration

### Background Processing

Long-running workflows automatically use Laravel's queue system:

```php
// This workflow will run in the background
$workflow = WorkflowBuilder::create('background-workflow')
    ->step('heavy-processing', HeavyProcessingAction::class)
    ->delay(hours: 1)
    ->step('followup', FollowupAction::class)
    ->build();

// Start it
$instance = $workflow->start($data);
```

### Queue Configuration

Configure queue settings in your config file:

```php
// config/workflow-engine.php
return [
    'queue' => [
        'connection' => 'redis',
        'queue' => 'workflows',
        'retry_after' => 300,
        'max_attempts' => 3,
    ],
];
```

### Priority Queues

Set priorities for workflow jobs:

```php
$workflow = WorkflowBuilder::create('priority-workflow')
    ->priority('high')
    ->step('urgent-task', UrgentTaskAction::class)
    ->build();
```

## Monitoring and Observability

### Workflow Events

Listen to workflow events:

```php
use SolutionForest\WorkflowEngine\Events\WorkflowStarted;
use SolutionForest\WorkflowEngine\Events\WorkflowCompletedEvent;
use SolutionForest\WorkflowEngine\Events\WorkflowFailedEvent;

// In your EventServiceProvider
protected $listen = [
    WorkflowStarted::class => [
        LogWorkflowStarted::class,
    ],
    WorkflowCompletedEvent::class => [
        LogWorkflowCompleted::class,
        SendCompletionNotification::class,
    ],
    WorkflowFailedEvent::class => [
        LogWorkflowFailure::class,
        AlertAdministrators::class,
    ],
];
```

### Custom Metrics

Track custom metrics:

```php
class MetricsAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $startTime = microtime(true);
        
        // Your business logic
        $result = $this->performOperation();
        
        $duration = microtime(true) - $startTime;
        
        // Track metrics
        Metrics::timing('workflow.step.duration', $duration, [
            'workflow' => $context->getWorkflowName(),
            'step' => $context->getCurrentStepId(),
        ]);
        
        return ActionResult::success(['result' => $result]);
    }
}
```

### Health Checks

Monitor workflow health:

```php
Route::get('/health/workflows', function() {
    $stats = WorkflowEngine::getHealthStats();
    
    return response()->json([
        'status' => $stats['failed_count'] > 10 ? 'unhealthy' : 'healthy',
        'running_workflows' => $stats['running_count'],
        'failed_workflows' => $stats['failed_count'],
        'average_duration' => $stats['avg_duration'],
    ]);
});
```

## Testing Workflows

### Unit Testing Actions

Test individual actions:

```php
class ProcessPaymentActionTest extends TestCase
{
    public function test_successful_payment()
    {
        $context = new WorkflowContext([
            'payment' => ['amount' => 100, 'token' => 'tok_123']
        ], 'workflow-1', 'payment-step');
        
        $action = new ProcessPaymentAction();
        $result = $action->execute($context);
        
        $this->assertTrue($result->success);
        $this->assertArrayHasKey('payment_id', $result->data);
    }
}
```

### Integration Testing

Test complete workflows:

```php
class OrderWorkflowTest extends TestCase
{
    public function test_complete_order_workflow()
    {
        $order = Order::factory()->create();
        
        $workflow = WorkflowBuilder::create('test-order')
            ->step('validate', ValidateOrderAction::class)
            ->step('process-payment', ProcessPaymentAction::class)
            ->step('fulfill', FulfillOrderAction::class)
            ->build();
        
        $instance = $workflow->start(['order' => $order]);
        
        // Simulate workflow execution
        $instance->run();
        
        $this->assertEquals(WorkflowState::Completed, $instance->getState());
        $this->assertTrue($order->fresh()->is_fulfilled);
    }
}
```

### Mocking External Services

Mock external dependencies:

```php
class ExternalApiActionTest extends TestCase
{
    public function test_api_call_with_mock()
    {
        Http::fake([
            'api.example.com/*' => Http::response(['success' => true], 200)
        ]);
        
        $context = new WorkflowContext(['data' => 'test'], 'workflow-1', 'api-step');
        $action = new ExternalApiAction();
        $result = $action->execute($context);
        
        $this->assertTrue($result->success);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/webhook';
        });
    }
}
```
