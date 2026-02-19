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
- Comparisons: `>`, `<`, `>=`, `<=`, `===`, `!==`, `==`, `!=`
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

### Configuring Retries via Builder

Configure automatic retries when adding steps to the workflow:

```php
$workflow = WorkflowBuilder::create('robust-workflow')
    ->addStep('api-call', ApiCallAction::class, [], null, 3)        // 3 retry attempts
    ->addStep('database-op', DatabaseAction::class, [], null, 5)    // 5 retry attempts
    ->addStep('quick-task', QuickAction::class, [], '30s', 2)       // 30s timeout, 2 retries
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
            return ActionResult::failure('Payment failed: ' . $e->getMessage(), [
                'error_type' => 'payment_failure',
                'original_error' => $e->getMessage()
            ]);
        }
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('payment');
    }

    public function getName(): string
    {
        return 'Process Payment';
    }

    public function getDescription(): string
    {
        return 'Processes customer payment via configured gateway';
    }
}
```

## Timeouts

### Step-Level Timeouts

Set timeouts for individual steps using the builder:

```php
$workflow = WorkflowBuilder::create('timed-workflow')
    ->addStep('quick-operation', QuickAction::class, timeout: 30)      // 30 seconds
    ->addStep('slow-operation', SlowAction::class, timeout: '5m')      // 5 minutes
    ->addStep('long-task', LongTaskAction::class, timeout: '2h')       // 2 hours
    ->build();
```

Timeout string formats: `'30s'` (seconds), `'5m'` (minutes), `'2h'` (hours), `'1d'` (days).

### Timeout Handling

Handle timeouts gracefully in your actions:

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

    public function canExecute(WorkflowContext $context): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'Time Sensitive Operation';
    }

    public function getDescription(): string
    {
        return 'Performs a time-sensitive operation with graceful timeout';
    }
}
```

## Conditional Workflows

### Simple Conditions

Use the `when()` method on the builder for conditional steps:

```php
$workflow = WorkflowBuilder::create('conditional-flow')
    ->addStep('validate', ValidateAction::class)
    ->when('user.type === "premium"', fn($builder) =>
        $builder->addStep('premium-benefits', PremiumBenefitsAction::class)
    )
    ->when('order.total > 100', fn($builder) =>
        $builder->addStep('apply-discount', DiscountAction::class)
    )
    ->build();
```

### Condition Steps

Use the built-in `ConditionAction` for inline condition evaluation:

```php
$workflow = WorkflowBuilder::create('condition-check')
    ->condition('user.verified === true')
    ->addStep('proceed', ProceedAction::class)
    ->build();
```

### Dynamic Conditions

Evaluate conditions at runtime within your action:

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

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('user');
    }

    public function getName(): string
    {
        return 'Dynamic Condition Check';
    }

    public function getDescription(): string
    {
        return 'Evaluates conditions dynamically at runtime';
    }
}
```

## Quick Workflow Templates

Use pre-built workflow patterns for common scenarios:

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;

// User onboarding template
$onboarding = WorkflowBuilder::quick()
    ->userOnboarding('premium-onboarding')
    ->then(SetupPremiumFeaturesAction::class)
    ->build();

// Order processing template
$orderFlow = WorkflowBuilder::quick()
    ->orderProcessing('express-order')
    ->build();

// Document approval template
$approval = WorkflowBuilder::quick()
    ->documentApproval('legal-review')
    ->addStep('legal-sign-off', LegalSignOffAction::class)
    ->build();
```

## Monitoring and Observability

### Workflow Events

Listen to workflow events in your Laravel application:

```php
use SolutionForest\WorkflowEngine\Events\WorkflowStarted;
use SolutionForest\WorkflowEngine\Events\WorkflowCompletedEvent;
use SolutionForest\WorkflowEngine\Events\WorkflowFailedEvent;
use SolutionForest\WorkflowEngine\Events\WorkflowCancelled;
use SolutionForest\WorkflowEngine\Events\StepCompletedEvent;
use SolutionForest\WorkflowEngine\Events\StepFailedEvent;

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
    StepCompletedEvent::class => [
        TrackStepProgress::class,
    ],
    StepFailedEvent::class => [
        LogStepFailure::class,
    ],
];
```

### Workflow Status Monitoring

Track workflow status programmatically:

```php
$engine = app(WorkflowEngine::class);

// Get workflow status
$status = $engine->getStatus($instanceId);
// Returns: workflow_id, name, state, current_step, progress, created_at, updated_at

// List workflows with filters
$running = $engine->listWorkflows(['state' => 'running']);
$recent = $engine->listWorkflows(['state' => 'failed', 'limit' => 10]);

// Get workflow instance details
$instance = $engine->getInstance($instanceId);
echo $instance->getProgress(); // 0.0 to 100.0
echo $instance->getState()->label(); // 'Running', 'Completed', etc.
echo $instance->getStatusSummary(); // Full status summary array
```

## Testing Workflows

### Unit Testing Actions

Test individual actions with a `WorkflowContext`:

```php
class ProcessPaymentActionTest extends TestCase
{
    public function test_successful_payment()
    {
        $context = new WorkflowContext(
            workflowId: 'workflow-1',
            stepId: 'payment-step',
            data: [
                'payment' => ['amount' => 100, 'token' => 'tok_123']
            ]
        );

        $action = new ProcessPaymentAction();
        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());
        $this->assertNotEmpty($result->get('payment_id'));
    }
}
```

### Integration Testing

Test complete workflows using the engine:

```php
class OrderWorkflowTest extends TestCase
{
    public function test_complete_order_workflow()
    {
        $engine = app(WorkflowEngine::class);

        $definition = WorkflowBuilder::create('test-order')
            ->addStep('validate', ValidateOrderAction::class)
            ->addStep('process-payment', ProcessPaymentAction::class)
            ->addStep('fulfill', FulfillOrderAction::class)
            ->build();

        $instanceId = $engine->start('test-order-1', $definition->toArray(), [
            'order' => ['id' => 1, 'total' => 99.99]
        ]);

        $instance = $engine->getInstance($instanceId);
        $this->assertEquals(WorkflowState::COMPLETED, $instance->getState());
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

        $context = new WorkflowContext(
            workflowId: 'workflow-1',
            stepId: 'api-step',
            data: ['data' => 'test']
        );

        $action = new ExternalApiAction();
        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/webhook';
        });
    }
}
```
