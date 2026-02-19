# API Reference

## WorkflowBuilder

The `WorkflowBuilder` class provides a fluent API for creating workflows.

### Methods

#### `create(string $name): static`

Creates a new workflow builder instance. Name must start with a letter and contain only letters, numbers, hyphens, and underscores.

```php
$builder = WorkflowBuilder::create('my-workflow');
```

#### `description(string $description): self`

Sets a human-readable description for the workflow.

```php
$builder->description('Handles complete user onboarding process');
```

#### `version(string $version): self`

Sets the workflow version for change tracking.

```php
$builder->version('2.1.0');
```

#### `addStep(string $id, string|WorkflowAction $action, array $config = [], string|int|null $timeout = null, int $retryAttempts = 0): self`

Adds a custom action step to the workflow. Timeout accepts seconds as integer or string format (`'30s'`, `'5m'`, `'2h'`, `'1d'`). Retry attempts must be between 0 and 10.

```php
$builder->addStep('process-payment', ProcessPaymentAction::class);
$builder->addStep('process-payment', ProcessPaymentAction::class, ['currency' => 'USD'], 30, 3);
$builder->addStep('slow-task', SlowAction::class, timeout: '5m', retryAttempts: 3);
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

#### `delay(int $seconds = null, int $minutes = null, int $hours = null): self`

Adds a delay step to the workflow.

```php
$builder->delay(minutes: 30);
$builder->delay(hours: 2);
$builder->delay(hours: 1, minutes: 30); // 1.5 hour delay
```

#### `condition(string $condition): self`

Adds a condition check step for workflow branching.

```php
$builder->condition('user.verified === true');
```

#### `when(string $condition, callable $callback): self`

Adds conditional logic to the workflow. Steps added in the callback are only executed when the condition is met.

```php
$builder->when('user.age >= 18', function($builder) {
    $builder->addStep('verify-identity', VerifyIdentityAction::class);
});
```

#### `startWith(string|WorkflowAction $action, array $config = [], string|int|null $timeout = null, int $retryAttempts = 0): self`

Adds the first step in a workflow (syntactic sugar for better readability). Auto-generates a step ID.

```php
$builder->startWith(ValidateInputAction::class, ['strict' => true]);
```

#### `then(string|WorkflowAction $action, array $config = [], string|int|null $timeout = null, int $retryAttempts = 0): self`

Adds a sequential step (syntactic sugar for better readability). Auto-generates a step ID.

```php
$builder->then(ProcessDataAction::class)->then(SaveResultAction::class);
```

#### `withMetadata(array $metadata): self`

Adds custom metadata to the workflow definition.

```php
$builder->withMetadata([
    'author' => 'John Doe',
    'department' => 'Engineering',
    'priority' => 'high'
]);
```

#### `build(): WorkflowDefinition`

Builds and returns the workflow definition.

```php
$workflow = $builder->build();
```

#### `quick(): QuickWorkflowBuilder` (static)

Returns a `QuickWorkflowBuilder` instance for pre-built common workflow patterns.

```php
$workflow = WorkflowBuilder::quick()->userOnboarding('new-user-flow');
$workflow = WorkflowBuilder::quick()->orderProcessing();
$workflow = WorkflowBuilder::quick()->documentApproval();
```

## WorkflowAction Interface

The `WorkflowAction` interface defines the contract for all workflow step implementations.

### Methods

#### `execute(WorkflowContext $context): ActionResult`

Execute the workflow action with the provided context. This is the core method where the action's business logic is implemented.

#### `canExecute(WorkflowContext $context): bool`

Check if this action can be executed with the given context. Allows for pre-execution validation and conditional logic.

#### `getName(): string`

Get the human-readable display name for this action.

#### `getDescription(): string`

Get a detailed description of what this action does.

```php
class CreateUserProfileAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $userData = $context->getData('user');
        $profile = UserProfile::create(['user_id' => $userData['id']]);
        return ActionResult::success(['profile_id' => $profile->id]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('user.id');
    }

    public function getName(): string
    {
        return 'Create User Profile';
    }

    public function getDescription(): string
    {
        return 'Creates a new user profile in the database';
    }
}
```

## WorkflowContext

The `WorkflowContext` class holds the data that flows through the workflow. It is an immutable `final readonly` class.

### Properties

#### `readonly string $workflowId`

The unique workflow instance ID.

#### `readonly string $stepId`

The ID of the current step.

#### `readonly array $data`

The workflow data.

#### `readonly array $config`

Step-specific configuration parameters.

#### `readonly ?WorkflowInstance $instance`

The associated workflow instance (if available).

#### `readonly DateTime $executedAt`

Timestamp when this context was created.

### Methods

#### `getData(?string $key = null, mixed $default = null): mixed`

Gets data from the context. Without parameters returns all data. Supports dot notation for nested access.

```php
$allData = $context->getData();
$user = $context->getData('user');
$userName = $context->getData('user.name');
$email = $context->getData('user.email', 'unknown@example.com');
```

#### `with(string $key, mixed $value): static`

Creates a new context with a single data value set. Supports dot notation.

```php
$newContext = $context->with('user.verified', true);
$newContext = $context->with('order.items.0.quantity', 2);
```

#### `withData(array $newData): static`

Creates a new context with additional data merged in (immutable operation).

```php
$newContext = $context->withData([
    'order' => ['id' => 123, 'total' => 99.99],
    'payment' => ['method' => 'credit_card']
]);
```

#### `hasData(string $key): bool`

Check if a data key exists in the context. Supports dot notation.

```php
if ($context->hasData('user.email')) {
    // User email is available
}
```

#### `getConfig(?string $key = null, mixed $default = null): mixed`

Get configuration value(s) for the current step. Supports dot notation.

```php
$timeout = $context->getConfig('timeout', 30);
$retries = $context->getConfig('retry.attempts', 3);
$allConfig = $context->getConfig(); // Gets all configuration
```

#### `getWorkflowId(): string`

Get the workflow identifier.

#### `getStepId(): string`

Get the current step identifier.

#### `toArray(): array`

Convert the context to an array representation for serialization.

## WorkflowState

Enum representing workflow states.

### Values

- `PENDING` - Workflow is created but not started
- `RUNNING` - Workflow is currently executing
- `WAITING` - Workflow is waiting for external input or conditions
- `PAUSED` - Workflow is temporarily paused
- `COMPLETED` - Workflow finished successfully
- `FAILED` - Workflow failed with an error
- `CANCELLED` - Workflow was cancelled

### Methods

#### `isActive(): bool`

Returns true for active states (`PENDING`, `RUNNING`, `WAITING`, `PAUSED`).

```php
if ($state->isActive()) {
    // Workflow can still progress
}
```

#### `isFinished(): bool`

Returns true for terminal states (`COMPLETED`, `FAILED`, `CANCELLED`).

```php
if ($state->isFinished()) {
    // Workflow execution has ended
}
```

#### `isSuccessful(): bool`

Returns true only for `COMPLETED` state.

#### `isError(): bool`

Returns true only for `FAILED` state.

#### `color(): string`

Returns a color name representing the state.

```php
WorkflowState::PENDING->color();   // 'gray'
WorkflowState::RUNNING->color();   // 'blue'
WorkflowState::WAITING->color();   // 'yellow'
WorkflowState::PAUSED->color();    // 'orange'
WorkflowState::COMPLETED->color(); // 'green'
WorkflowState::FAILED->color();    // 'red'
WorkflowState::CANCELLED->color(); // 'purple'
```

#### `icon(): string`

Returns an emoji icon for the state.

```php
WorkflowState::RUNNING->icon();   // '▶️'
WorkflowState::COMPLETED->icon(); // '✅'
WorkflowState::FAILED->icon();    // '❌'
```

#### `label(): string`

Returns a human-readable label.

```php
WorkflowState::PENDING->label();   // 'Pending'
WorkflowState::RUNNING->label();   // 'Running'
WorkflowState::COMPLETED->label(); // 'Completed'
```

#### `description(): string`

Returns a detailed description of what the state means.

```php
WorkflowState::RUNNING->description();
// 'The workflow is actively executing steps. One or more actions are currently being processed.'
```

#### `canTransitionTo(WorkflowState $state): bool`

Checks if the state can transition to another state.

```php
if ($currentState->canTransitionTo(WorkflowState::COMPLETED)) {
    // Can transition to completed
}
```

Valid transitions:
- `PENDING` -> `RUNNING`, `CANCELLED`
- `RUNNING` -> `WAITING`, `PAUSED`, `COMPLETED`, `FAILED`, `CANCELLED`
- `WAITING` -> `RUNNING`, `FAILED`, `CANCELLED`
- `PAUSED` -> `RUNNING`, `CANCELLED`
- Terminal states (`COMPLETED`, `FAILED`, `CANCELLED`) -> none

#### `getValidTransitions(): array`

Returns all possible states that can be transitioned to from this state.

```php
$validStates = WorkflowState::RUNNING->getValidTransitions();
// [WAITING, PAUSED, COMPLETED, FAILED, CANCELLED]
```

## ActionResult

Represents the result of an action execution. This is an immutable value object.

### Static Methods

#### `success(array $data = [], array $metadata = []): static`

Creates a successful result with optional data and metadata.

```php
return ActionResult::success(['user_id' => 123]);
return ActionResult::success(
    ['processed_count' => 50],
    ['execution_time_ms' => 1250]
);
```

#### `failure(string $errorMessage, array $metadata = []): static`

Creates a failed result with an error message and optional metadata.

```php
return ActionResult::failure('Payment failed');
return ActionResult::failure('API rate limit exceeded', [
    'retry_after' => 3600,
    'requests_remaining' => 0
]);
```

### Methods

#### `isSuccess(): bool`

Whether the action succeeded.

#### `isFailure(): bool`

Whether the action failed.

#### `getErrorMessage(): ?string`

Returns the error message for failed results, or null for successful results.

#### `getData(): array`

Returns the result data array. Empty array for failed results.

#### `hasData(): bool`

Returns true if the result contains any data.

#### `getMetadata(): array`

Returns additional execution metadata.

#### `get(string $key, mixed $default = null): mixed`

Get a specific data value using dot notation.

```php
$userId = $result->get('user.id');
$email = $result->get('user.email', 'N/A');
```

#### `withMetadata(array $metadata): static`

Creates a new result with additional metadata merged.

```php
$resultWithMetadata = $result->withMetadata([
    'execution_time' => 150,
    'cache_hit' => true
]);
```

#### `withMetadataEntry(string $key, mixed $value): self`

Creates a new result with a single additional metadata entry.

#### `mergeData(array $additionalData): self`

Creates a new successful result by merging data. Throws `LogicException` if called on a failed result.

```php
$result1 = ActionResult::success(['user_id' => 123]);
$result2 = $result1->mergeData(['email' => 'user@example.com']);
// result2 data: ['user_id' => 123, 'email' => 'user@example.com']
```

#### `toArray(): array`

Convert the result to an array representation for serialization.

```php
$array = $result->toArray();
// ['success' => true, 'error_message' => null, 'data' => [...], 'metadata' => [...]]
```

## SimpleWorkflow

Helper class for simplified workflow creation and execution.

### Constructor

```php
$simple = new SimpleWorkflow($storageAdapter, $eventDispatcher);
```

### Methods

#### `sequential(array $steps): string`

Creates and executes a workflow with sequential steps. Returns the workflow instance ID.

```php
$instanceId = $simple->sequential([
    'step1' => StepOneAction::class,
    'step2' => StepTwoAction::class,
    'step3' => StepThreeAction::class
]);
```

#### `runAction(string $actionClass, array $config = [], array $context = []): ActionResult`

Executes a single action directly without creating a workflow.

#### `executeBuilder(WorkflowBuilder $builder, array $context = []): string`

Executes a workflow from a builder instance.

#### `resume(string $instanceId): WorkflowInstance`

Resumes a paused or pending workflow.

#### `getStatus(string $instanceId): array`

Gets the status of a workflow instance.

#### `getEngine(): WorkflowEngine`

Returns the underlying workflow engine instance.

## QuickWorkflowBuilder

Pre-built workflow patterns for common business scenarios. Access via `WorkflowBuilder::quick()`.

### Methods

#### `userOnboarding(string $name = 'user-onboarding'): WorkflowBuilder`

Creates a user onboarding workflow with standard steps (welcome email, delay, profile creation, role assignment).

#### `orderProcessing(string $name = 'order-processing'): WorkflowBuilder`

Creates an order processing workflow (validate, charge payment, update inventory, confirmation email).

#### `documentApproval(string $name = 'document-approval'): WorkflowBuilder`

Creates a document approval workflow (submit, assign reviewer, review request email, review, conditional approve/reject).

```php
$workflow = WorkflowBuilder::quick()
    ->userOnboarding('premium-onboarding')
    ->then(SetupPremiumFeaturesAction::class)
    ->build();
```

## Attributes

### @WorkflowStep

Marks a class as a workflow step with metadata.

```php
use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;

#[WorkflowStep(
    id: 'create_profile',
    name: 'Create User Profile',
    description: 'Creates a new user profile in the database',
    config: ['template' => 'basic'],
    required: true,
    order: 1
)]
class CreateUserProfileAction implements WorkflowAction
{
    // ...
}
```

### @Timeout

Sets a timeout for an action. Accepts `seconds`, `minutes`, and/or `hours`. Provides a calculated `totalSeconds` property.

```php
use SolutionForest\WorkflowEngine\Attributes\Timeout;

#[Timeout(seconds: 30)]
#[Timeout(minutes: 5)]
#[Timeout(minutes: 5, seconds: 30)] // 5 minutes 30 seconds
class MyAction implements WorkflowAction
{
    // ...
}
```

### @Retry

Configures retry behavior for an action.

```php
use SolutionForest\WorkflowEngine\Attributes\Retry;

#[Retry(attempts: 3, backoff: 'exponential')]
#[Retry(attempts: 5, backoff: 'exponential', delay: 1000, maxDelay: 30000)]
class MyAction implements WorkflowAction
{
    // ...
}
```

Parameters:
- `attempts` (int, default 3) - Number of retry attempts
- `backoff` (`'linear'` | `'exponential'` | `'fixed'`) - Backoff strategy
- `delay` (int, default 1000) - Base delay in milliseconds
- `maxDelay` (int, default 30000) - Maximum delay cap in milliseconds

### @Condition

Sets a condition for when an action should execute. This attribute is repeatable.

```php
use SolutionForest\WorkflowEngine\Attributes\Condition;

#[Condition('user.email is not null')]
#[Condition('order.amount > 100')]
#[Condition('user.premium = true', operator: 'or')]
class ConditionalAction implements WorkflowAction
{
    // ...
}
```

Parameters:
- `expression` (string) - The condition expression
- `operator` (`'and'` | `'or'`, default `'and'`) - How to combine with other conditions

## Events

The workflow engine dispatches the following events:

### WorkflowStarted

Dispatched when a workflow begins execution.

Properties: `workflowId`, `name`, `context` (array)

### WorkflowCompletedEvent

Dispatched when a workflow finishes successfully.

Properties: `instance` (WorkflowInstance)

### WorkflowFailedEvent

Dispatched when a workflow fails due to errors.

Properties: `instance` (WorkflowInstance), `exception` (Throwable)

### WorkflowCancelled

Dispatched when a workflow is cancelled.

Properties: `workflowId`, `name`, `reason` (string)

### StepCompletedEvent

Dispatched when a step completes successfully.

Properties: `instance`, `step`, `result`

### StepFailedEvent

Dispatched when a step fails.

Properties: `instance`, `step`, `exception` (Throwable)

## Exceptions

### WorkflowException (abstract base)

Base exception for all workflow exceptions. Provides `getContext()`, `getUserMessage()`, `getDebugInfo()`, `getSuggestions()`.

### InvalidWorkflowDefinitionException

Thrown for validation errors in workflow definitions. Factory methods: `missingRequiredField()`, `invalidStep()`, `invalidStepId()`, `invalidRetryAttempts()`, `invalidTimeout()`, `duplicateStepId()`, `invalidName()`, `invalidCondition()`, `invalidDelay()`, `emptyWorkflow()`, `actionNotFound()`, `invalidActionClass()`.

### WorkflowInstanceNotFoundException

Thrown when a workflow instance cannot be found. Factory methods: `notFound()`, `malformedId()`, `storageConnectionError()`.

### InvalidWorkflowStateException

Thrown for invalid state transitions. Factory methods: `cannotResumeCompleted()`, `cannotCancelFailed()`, `alreadyRunning()`, `fromInstanceTransition()`.

### ActionNotFoundException

Thrown when an action class cannot be found or doesn't implement the interface. Factory methods: `classNotFound()`, `invalidInterface()`, `actionNotFound()`, `invalidActionClass()`.

### StepExecutionException

Thrown when a step fails during execution. Factory methods: `actionClassNotFound()`, `invalidActionClass()`, `timeout()`, `fromException()`, `actionFailed()`.
