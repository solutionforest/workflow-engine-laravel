# API Reference

## WorkflowBuilder

The `WorkflowBuilder` class provides a fluent API for creating workflows.

### Methods

#### `create(string $name): self`

Creates a new workflow builder instance.

```php
$builder = WorkflowBuilder::create('my-workflow');
```

#### `step(string $id, string $actionClass): self`

Adds a custom action step to the workflow.

```php
$builder->step('process-payment', ProcessPaymentAction::class);
```

#### `email(string $template, array $options = []): self`

Adds an email step to the workflow.

```php
$builder->email('welcome-email', [
    'to' => '{{ user.email }}',
    'subject' => 'Welcome {{ user.name }}!',
    'data' => ['welcome_bonus' => 100]
]);
```

#### `http(string $method, string $url, array $data = []): self`

Adds an HTTP request step to the workflow.

```php
$builder->http('POST', 'https://api.example.com/webhooks', [
    'event' => 'user_registered',
    'user_id' => '{{ user.id }}'
]);
```

#### `delay(int $seconds = null, int $minutes = null, int $hours = null, int $days = null): self`

Adds a delay step to the workflow.

```php
$builder->delay(minutes: 30);
$builder->delay(hours: 2);
$builder->delay(days: 1);
```

#### `when(string $condition, callable $callback): self`

Adds conditional logic to the workflow.

```php
$builder->when('user.age >= 18', function($builder) {
    $builder->step('verify-identity', VerifyIdentityAction::class);
});
```

#### `retry(int $attempts, string $backoff = 'linear'): self`

Configures retry behavior for the previous step.

```php
$builder->step('api-call', ApiCallAction::class)
    ->retry(attempts: 3, backoff: 'exponential');
```

#### `timeout(int $seconds = null, int $minutes = null, int $hours = null): self`

Sets a timeout for the previous step.

```php
$builder->step('long-operation', LongOperationAction::class)
    ->timeout(minutes: 5);
```

#### `build(): WorkflowDefinition`

Builds and returns the workflow definition.

```php
$workflow = $builder->build();
```

## WorkflowContext

The `WorkflowContext` class holds the data that flows through the workflow.

### Properties

#### `readonly array $data`

The workflow data.

#### `readonly string $workflowId`

The unique workflow instance ID.

#### `readonly string $currentStepId`

The ID of the current step.

### Methods

#### `getData(string $key = null): mixed`

Gets data from the context.

```php
$allData = $context->getData();
$user = $context->getData('user');
$userName = $context->getData('user.name');
```

#### `with(array $data): self`

Creates a new context with additional data.

```php
$newContext = $context->with(['step_result' => $result]);
```

#### `withData(string $key, mixed $value): self`

Creates a new context with a single data value.

```php
$newContext = $context->withData('status', 'completed');
```

## WorkflowState

Enum representing workflow states.

### Values

- `Pending` - Workflow is created but not started
- `Running` - Workflow is currently executing
- `Completed` - Workflow finished successfully
- `Failed` - Workflow failed with an error
- `Cancelled` - Workflow was cancelled
- `Paused` - Workflow is temporarily paused

### Methods

#### `color(): string`

Returns a color representing the state.

```php
WorkflowState::Running->color(); // 'blue'
WorkflowState::Completed->color(); // 'green'
WorkflowState::Failed->color(); // 'red'
```

#### `icon(): string`

Returns an emoji icon for the state.

```php
WorkflowState::Running->icon(); // '▶️'
WorkflowState::Completed->icon(); // '✅'
WorkflowState::Failed->icon(); // '❌'
```

#### `label(): string`

Returns a human-readable label.

```php
WorkflowState::Running->label(); // 'In Progress'
WorkflowState::Completed->label(); // 'Completed'
```

#### `canTransitionTo(WorkflowState $state): bool`

Checks if the state can transition to another state.

```php
if ($currentState->canTransitionTo(WorkflowState::Completed)) {
    // Can transition to completed
}
```

## ActionResult

Represents the result of an action execution.

### Static Methods

#### `success(array $data = []): self`

Creates a successful result.

```php
return ActionResult::success(['user_id' => 123]);
```

#### `failure(string $message, array $data = []): self`

Creates a failed result.

```php
return ActionResult::failure('Payment failed', ['error_code' => 'CARD_DECLINED']);
```

#### `retry(string $message = 'Retrying', array $data = []): self`

Creates a result that triggers a retry.

```php
return ActionResult::retry('Rate limited, retrying...', ['retry_after' => 60]);
```

### Properties

#### `readonly bool $success`

Whether the action succeeded.

#### `readonly string $message`

The result message.

#### `readonly array $data`

Additional result data.

## SimpleWorkflow

Helper class for creating common workflow patterns.

### Static Methods

#### `quick(): self`

Creates a new simple workflow builder.

```php
$workflow = SimpleWorkflow::quick()
    ->email('welcome', to: 'user@example.com')
    ->delay(days: 1)
    ->email('followup', to: 'user@example.com')
    ->build();
```

#### `sequential(array $steps): self`

Creates a workflow with sequential steps.

```php
$workflow = SimpleWorkflow::sequential([
    'step1' => StepOneAction::class,
    'step2' => StepTwoAction::class,
    'step3' => StepThreeAction::class
])->build();
```

## Attributes

### @WorkflowStep

Marks a method as a workflow step.

```php
use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;

class MyAction implements WorkflowAction
{
    #[WorkflowStep('my-step')]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action logic
    }
}
```

### @Timeout

Sets a timeout for an action.

```php
use SolutionForest\WorkflowEngine\Attributes\Timeout;

class MyAction implements WorkflowAction
{
    #[Timeout(minutes: 5)]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action logic
    }
}
```

### @Retry

Configures retry behavior for an action.

```php
use SolutionForest\WorkflowEngine\Attributes\Retry;

class MyAction implements WorkflowAction
{
    #[Retry(attempts: 3, backoff: 'exponential')]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action logic
    }
}
```

### @Condition

Sets a condition for when an action should execute.

```php
use SolutionForest\WorkflowEngine\Attributes\Condition;

class MyAction implements WorkflowAction
{
    #[Condition('user.age >= 18')]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Action logic
    }
}
```
