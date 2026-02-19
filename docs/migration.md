# Migration Guide

## Upgrading to v2.0 (Modern PHP Features)

This guide helps you migrate from the array-based workflow configuration to the new fluent API with PHP 8.3+ features.

### Breaking Changes

1. **Fluent API**: Array-based configuration is deprecated in favor of the new `WorkflowBuilder`
2. **Type Safety**: All classes now use readonly properties and strong typing
3. **Namespace Changes**: Some classes have moved to new namespaces
4. **Context Structure**: `WorkflowContext` is now immutable and readonly

### Migration Steps

#### 1. Update Composer Dependencies

```bash
composer update solution-forest/workflow-engine-laravel
```

#### 2. Publish New Configuration

```bash
php artisan vendor:publish --tag="workflow-engine-config" --force
```

#### 3. Run New Migrations

```bash
php artisan vendor:publish --tag="workflow-engine-migrations"
php artisan migrate
```

#### 4. Update Workflow Definitions

**Before (v1.x - Array Configuration):**

```php
$orderWorkflow = [
    'name' => 'order-processing',
    'steps' => [
        ['id' => 'validate', 'action' => ValidateOrderAction::class],
        ['id' => 'payment', 'action' => ProcessPaymentAction::class],
        ['id' => 'shipping', 'action' => CreateShipmentAction::class],
    ],
    'transitions' => [
        ['from' => 'validate', 'to' => 'payment'],
        ['from' => 'payment', 'to' => 'shipping'],
    ],
    'error_handling' => [
        'payment_failure' => [
            'retry_attempts' => 3,
            'compensation' => RefundAction::class,
        ],
    ],
];

$instance = WorkflowMastery::start($orderWorkflow, $data);
```

**After (v2.x - Fluent API):**

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;

$definition = WorkflowBuilder::create('order-processing')
    ->addStep('validate', ValidateOrderAction::class)
    ->addStep('payment', ProcessPaymentAction::class, [], null, 3) // 3 retry attempts
    ->addStep('shipping', CreateShipmentAction::class)
    ->build();

$engine = app(WorkflowEngine::class);
$instanceId = $engine->start('order-001', $definition->toArray(), $data);
```

#### 5. Update Action Classes

**Before (v1.x):**

```php
class ProcessPaymentAction
{
    public function execute($context)
    {
        $orderData = $context['order'] ?? [];

        // Process payment
        $result = $this->chargeCard($orderData);

        return [
            'success' => $result['success'],
            'data' => $result['data'] ?? [],
        ];
    }
}
```

**After (v2.x):**

```php
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\{WorkflowContext, ActionResult};

class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Process payment
        $result = $this->chargeCard($order);

        if ($result['success']) {
            return ActionResult::success($result['data']);
        }

        return ActionResult::failure('Payment failed', [
            'error' => $result['error'] ?? 'Unknown error'
        ]);
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

#### 6. Update Context Usage

**Before (v1.x):**

```php
// Context was a simple array
$context = [
    'user' => $user,
    'order' => $order,
    'step_results' => []
];

// Access data directly
$userId = $context['user']['id'];
$orderTotal = $context['order']['total'];
```

**After (v2.x):**

```php
// Context is now a readonly class
$context = new WorkflowContext(
    workflowId: $workflowId,
    stepId: $stepId,
    data: [
        'user' => $user,
        'order' => $order
    ]
);

// Use getter methods with dot notation
$user = $context->getData('user');
$userId = $context->getData('user.id');
$orderTotal = $context->getData('order.total');

// Create new context with additional data (immutable)
$newContext = $context->with('payment_result', $paymentData);

// Or merge multiple values
$newContext = $context->withData(['payment_result' => $paymentData, 'status' => 'paid']);
```

#### 7. Update Error Handling

**Before (v1.x):**

```php
try {
    $result = $action->execute($context);
    if (!$result['success']) {
        // Handle error
        $this->handleError($result['error']);
    }
} catch (\Exception $e) {
    // Log error
    Log::error('Workflow failed', ['error' => $e->getMessage()]);
}
```

**After (v2.x):**

```php
try {
    $result = $action->execute($context);

    if ($result->isSuccess()) {
        $this->proceedToNextStep($result);
    } else {
        $this->handleFailure($result);
        Log::warning('Action failed', [
            'error' => $result->getErrorMessage(),
            'metadata' => $result->getMetadata()
        ]);
    }
} catch (\Exception $e) {
    Log::error('Workflow failed', [
        'workflow_id' => $context->workflowId,
        'step_id' => $context->stepId,
        'error' => $e->getMessage()
    ]);
}
```

### New Features You Can Adopt

#### 1. Modern Enums for State Management

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

// Rich enum with methods
$state = WorkflowState::RUNNING;
echo $state->label();       // "Running"
echo $state->color();       // "blue"
echo $state->icon();        // "▶️"
echo $state->description(); // Detailed description

// State category checks
$state->isActive();     // true for PENDING, RUNNING, WAITING, PAUSED
$state->isFinished();   // true for COMPLETED, FAILED, CANCELLED
$state->isSuccessful(); // true only for COMPLETED
$state->isError();      // true only for FAILED

// Smart state transitions
if ($state->canTransitionTo(WorkflowState::COMPLETED)) {
    $workflow->complete();
}

$validTargets = $state->getValidTransitions(); // Array of valid target states
```

#### 2. Attribute-Based Configuration

```php
use SolutionForest\WorkflowEngine\Attributes\{WorkflowStep, Timeout, Retry, Condition};

#[WorkflowStep(
    id: 'process-payment',
    name: 'Process Payment',
    description: 'Processes customer payment'
)]
#[Timeout(minutes: 5)]
#[Retry(attempts: 3, backoff: 'exponential')]
#[Condition('order.amount > 0')]
class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action implementation
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
        return 'Processes customer payment';
    }
}
```

#### 3. Quick Workflow Templates

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;

// Pre-built workflow templates
$onboarding = WorkflowBuilder::quick()
    ->userOnboarding('premium-onboarding')
    ->then(SetupPremiumFeaturesAction::class)
    ->build();

$orderFlow = WorkflowBuilder::quick()
    ->orderProcessing('express-order')
    ->build();

$approval = WorkflowBuilder::quick()
    ->documentApproval('legal-review')
    ->build();
```

#### 4. Smart Actions

```php
// HTTP actions with template processing
$workflow = WorkflowBuilder::create('api-integration')
    ->http('https://api.example.com/webhook', 'POST', [
        'user_id' => '{{ user.id }}',
        'event' => 'user_registered',
    ])
    ->build();

// Conditional actions
$workflow = WorkflowBuilder::create('conditional-flow')
    ->when('user.age >= 18', fn($builder) =>
        $builder->addStep('adult-verification', AdultVerificationAction::class)
    )
    ->build();
```

### Common Migration Issues

#### Issue: Missing Type Declarations

**Error:**
```
TypeError: Argument 1 passed to WorkflowContext::__construct() must be of type array
```

**Solution:**
Use named parameters with the WorkflowContext constructor:

```php
// Wrong
$context = new WorkflowContext($user, $workflowId, $stepId);

// Correct
$context = new WorkflowContext(
    workflowId: $workflowId,
    stepId: $stepId,
    data: ['user' => $user]
);
```

#### Issue: Property Access on Readonly Classes

**Error:**
```
Error: Cannot modify readonly property WorkflowContext::$data
```

**Solution:**
Use immutable methods instead of direct property modification:

```php
// Wrong
$context->data['new_key'] = $value;

// Correct - single value
$context = $context->with('new_key', $value);

// Correct - multiple values
$context = $context->withData(['new_key' => $value, 'other' => $other]);
```

#### Issue: Namespace Changes

**Error:**
```
Class 'SolutionForest\WorkflowMastery\...' not found
```

**Solution:**
Update your use statements:

```php
// Old namespace
use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;

// New namespace
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
```

#### Issue: ActionResult API Changes

**Error:**
```
Call to undefined method ActionResult::retry()
```

**Solution:**
Use the new ActionResult API:

```php
// Old API
return ActionResult::retry('Rate limited');
$result->success;  // property access
$result->message;  // property access

// New API
return ActionResult::failure('Rate limited', ['retry_after' => 60]);
$result->isSuccess();       // method call
$result->getErrorMessage(); // method call
$result->getData();         // method call
$result->getMetadata();     // method call
```

### Performance Improvements

The new version includes several performance optimizations:

- **70% less code** for common workflow patterns
- **Faster execution** with readonly properties and optimized data structures
- **Better memory usage** with immutable contexts
- **Improved caching** of workflow definitions

### Getting Help

If you encounter issues during migration:

1. Review the [API Reference](api-reference.md)
2. Check the [Advanced Features](advanced-features.md) guide
3. Open an issue on [GitHub](https://github.com/solutionforest/workflow-engine-laravel/issues)
