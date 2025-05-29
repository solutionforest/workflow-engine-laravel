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

$workflow = WorkflowBuilder::create('order-processing')
    ->step('validate', ValidateOrderAction::class)
    ->step('payment', ProcessPaymentAction::class)
        ->retry(attempts: 3)
        ->onFailure(RefundAction::class)
    ->step('shipping', CreateShipmentAction::class)
    ->build();

$instance = $workflow->start($data);
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
        
        return ActionResult::failure('Payment failed', $result['error']);
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
$context = new WorkflowContext([
    'user' => $user,
    'order' => $order
], $workflowId, $stepId);

// Use getter methods
$user = $context->getData('user');
$userId = $context->getData('user.id');
$orderTotal = $context->getData('order.total');

// Create new context with additional data (immutable)
$newContext = $context->with(['payment_result' => $paymentData]);
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
    
    match($result->status) {
        'success' => $this->proceedToNextStep($result),
        'failure' => $this->handleFailure($result),
        'retry' => $this->scheduleRetry($result),
    };
} catch (\Exception $e) {
    Log::error('Workflow failed', [
        'workflow_id' => $context->workflowId,
        'step_id' => $context->currentStepId,
        'error' => $e->getMessage()
    ]);
}
```

### New Features You Can Adopt

#### 1. Modern Enums for State Management

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

// Rich enum with methods
$state = WorkflowState::Running;
echo $state->label();     // "In Progress"
echo $state->color();     // "blue"
echo $state->icon();      // "▶️"

// Smart state transitions
if ($state->canTransitionTo(WorkflowState::Completed)) {
    $workflow->complete();
}
```

#### 2. Attribute-Based Configuration

```php
use SolutionForest\WorkflowEngine\Attributes\{WorkflowStep, Timeout, Retry};

class ProcessPaymentAction implements WorkflowAction
{
    #[WorkflowStep('process-payment')]
    #[Timeout(minutes: 5)]
    #[Retry(attempts: 3, backoff: 'exponential')]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action implementation
    }
}
```

#### 3. Simple Workflow Helpers

```php
use SolutionForest\WorkflowEngine\Support\SimpleWorkflow;

// Quick common workflows
$onboarding = SimpleWorkflow::quick()
    ->email('welcome', to: 'user@example.com')
    ->delay(days: 1)
    ->email('tips', to: 'user@example.com')
    ->build();

// Sequential workflows
$approval = SimpleWorkflow::sequential([
    'submit' => SubmitAction::class,
    'review' => ReviewAction::class,
    'approve' => ApproveAction::class
])->build();
```

#### 4. Smart Actions

```php
// HTTP actions with template processing
$workflow = WorkflowBuilder::create('api-integration')
    ->http('POST', 'https://api.example.com/webhook', [
        'user_id' => '{{ user.id }}',
        'event' => 'user_registered',
        'timestamp' => '{{ now }}'
    ])
    ->build();

// Conditional actions
$workflow = WorkflowBuilder::create('conditional-flow')
    ->when('user.age >= 18', fn($builder) =>
        $builder->step('adult-verification', AdultVerificationAction::class)
    )
    ->build();
```

### Backward Compatibility

The package maintains backward compatibility for v1.x workflows during the transition period:

```php
// V1.x workflows still work (deprecated)
$legacyWorkflow = [
    'name' => 'legacy-workflow',
    'steps' => [/* ... */]
];

// But you'll see deprecation warnings
$instance = WorkflowEngine::startLegacy($legacyWorkflow, $data);
```

### Migration Helper Command

Use the built-in migration command to convert existing workflows:

```bash
# Convert a single workflow file
php artisan workflow:migrate app/Workflows/OrderProcessing.php

# Convert all workflows in a directory
php artisan workflow:migrate app/Workflows/ --recursive

# Dry run to see what would change
php artisan workflow:migrate app/Workflows/ --dry-run
```

### Testing Your Migration

1. **Run Your Test Suite**: Ensure all existing tests pass
2. **Check Logs**: Look for deprecation warnings
3. **Monitor Performance**: New features should improve performance
4. **Validate Functionality**: Verify workflows behave identically

### Common Migration Issues

#### Issue: Missing Type Declarations

**Error:**
```
TypeError: Argument 1 passed to WorkflowContext::__construct() must be of type array
```

**Solution:**
Ensure you're passing arrays to the WorkflowContext constructor:

```php
// Wrong
$context = new WorkflowContext($user, $workflowId, $stepId);

// Correct
$context = new WorkflowContext(['user' => $user], $workflowId, $stepId);
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

// Correct
$context = $context->withData('new_key', $value);
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

### Performance Improvements

The new version includes several performance optimizations:

- **70% less code** for common workflow patterns
- **Faster execution** with readonly properties and optimized data structures
- **Better memory usage** with immutable contexts
- **Improved caching** of workflow definitions

### Getting Help

If you encounter issues during migration:

1. Check the [troubleshooting guide](troubleshooting.md)
2. Review the [examples](../src/Examples/ModernWorkflowExamples.php)
3. Open an issue on [GitHub](https://github.com/solutionforest/workflow-engine-laravel/issues)
4. Join our [Discord community](https://discord.gg/workflow-engine)
