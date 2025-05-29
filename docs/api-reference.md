# API Reference

## WorkflowBuilder

The `WorkflowBuilder` class provides a fluent API for creating workflows.

### Methods

#### `create(string $name): self`

Creates a new workflow builder instance.

```php
$builder = WorkflowBuilder::create('my-workflow');
```

#### `addStep(string $id, string $actionClass, array $config = [], ?int $timeout = null, int $retryAttempts = 0): self`

Adds a custom action step to the workflow.

```php
$builder->addStep('process-payment', ProcessPaymentAction::class);
$builder->addStep('process-payment', ProcessPaymentAction::class, ['currency' => 'USD'], 30, 3);
```

#### `email(string $template, string $to, string $subject, array $data = []): self`

Adds an email step to the workflow.

```php
$builder->email('welcome-email', '{{ user.email }}', 'Welcome {{ user.name }}!', ['welcome_bonus' => 100]);
```

#### `http(string $url, string $method = 'GET', array $data = [], array $headers = []): self`

Adds an HTTP request step to the workflow.

```php
$builder->http('https://api.example.com/webhooks', 'POST', [
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
    $builder->addStep('verify-identity', VerifyIdentityAction::class);
});
```

#### `startWith(string $actionClass, array $config = [], ?int $timeout = null, int $retryAttempts = 0): self`

Adds the first step in a workflow (syntactic sugar for better readability).

```php
$builder->startWith(ValidateInputAction::class, ['strict' => true]);
```

#### `then(string $actionClass, array $config = [], ?int $timeout = null, int $retryAttempts = 0): self`

Adds a sequential step (syntactic sugar for better readability).

```php
$builder->then(ProcessDataAction::class)->then(SaveResultAction::class);
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
