# PHP 8.3+ Features & Simplification Opportunities

## Current Analysis

After reviewing the Laravel Workflow Engine library, I've identified significant opportunities to leverage modern PHP 8.3+ features and simplify the learning curve. Here's a comprehensive breakdown:

## 🚀 PHP 8.3+ Features Opportunities

### 1. **Enhanced Type System**

#### Current Issues:
- Mixed type declarations in some places
- Limited use of union types
- Some methods lack proper return types

#### Proposed Improvements:

```php
// Enhanced Step class with better types
class Step
{
    public function __construct(
        private readonly string $id,
        private readonly string|WorkflowAction|null $action = null, // Union types
        private readonly array $config = [],
        private readonly ?Duration $timeout = null, // Value objects
        private readonly int $retryAttempts = 0,
        private readonly string|WorkflowAction|null $compensationAction = null,
        private readonly array $conditions = [],
        private readonly array $prerequisites = []
    ) {}
}

// Better ActionResult with generic types (conceptual)
class ActionResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $errorMessage = null,
        private readonly array $data = [],
        private readonly array $metadata = []
    ) {}

    // Generic-like pattern for type-safe data access
    public function getData(string $key = null): mixed
    {
        return $key ? ($this->data[$key] ?? null) : $this->data;
    }
}
```

### 2. **Advanced Enums with Methods**

#### Current State:
```php
enum WorkflowState: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    // ... basic enum
}
```

#### Enhanced Version:
```php
enum WorkflowState: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case WAITING = 'waiting';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    // Add color coding for UI
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'blue',
            self::WAITING => 'yellow',
            self::PAUSED => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'purple',
        };
    }

    // Add icons for better UX
    public function icon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::RUNNING => '▶️',
            self::WAITING => '⏸️',
            self::PAUSED => '⏸️',
            self::COMPLETED => '✅',
            self::FAILED => '❌',
            self::CANCELLED => '🚫',
        };
    }

    // Transition validation
    public function canTransitionTo(self $state): bool
    {
        return match($this) {
            self::PENDING => in_array($state, [self::RUNNING, self::CANCELLED]),
            self::RUNNING => in_array($state, [self::WAITING, self::PAUSED, self::COMPLETED, self::FAILED, self::CANCELLED]),
            self::WAITING => in_array($state, [self::RUNNING, self::FAILED, self::CANCELLED]),
            self::PAUSED => in_array($state, [self::RUNNING, self::CANCELLED]),
            default => false, // Terminal states cannot transition
        };
    }
}
```

### 3. **Attributes for Configuration**

#### Current Challenge:
Complex array-based workflow definitions are hard to understand and type-check.

#### Proposed Solution:
```php
use SolutionForest\WorkflowMastery\Attributes\{WorkflowStep, Condition, Retry, Timeout};

#[WorkflowStep(
    id: 'send_email',
    name: 'Send Welcome Email',
    description: 'Sends a welcome email to the new user'
)]
#[Timeout(seconds: 30)]
#[Retry(attempts: 3, backoff: 'exponential')]
#[Condition('user.email is not null')]
class SendWelcomeEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Implementation
    }
}

// Auto-discovery of workflow steps via attributes
class WorkflowBuilder
{
    public function fromActions(array $actionClasses): WorkflowDefinition
    {
        $steps = [];
        foreach ($actionClasses as $actionClass) {
            $reflection = new ReflectionClass($actionClass);
            $stepAttr = $reflection->getAttributes(WorkflowStep::class)[0] ?? null;
            if ($stepAttr) {
                $stepConfig = $stepAttr->newInstance();
                $steps[] = new Step(
                    id: $stepConfig->id,
                    actionClass: $actionClass,
                    // ... auto-configure from attributes
                );
            }
        }
        return new WorkflowDefinition('auto-generated', '1.0', $steps);
    }
}
```

### 4. **Named Arguments & Fluent API**

#### Current Pain Point:
```php
// Hard to understand what parameters do
$engine->start('workflow-1', $definition, ['user_id' => 123, 'priority' => 'high']);
```

#### Improved with Named Arguments:
```php
// Much clearer intent
$engine->start(
    workflowId: 'user-onboarding-123',
    definition: $onboardingFlow,
    context: ['user_id' => 123, 'priority' => 'high']
);

// Even better with fluent builder
WorkflowBuilder::create('user-onboarding')
    ->addStep(action: SendWelcomeEmailAction::class)
    ->addStep(action: CreateUserProfileAction::class)
    ->addStep(action: AssignDefaultRoleAction::class)
    ->withContext(user_id: 123, priority: 'high')
    ->start();
```

### 5. **Readonly Classes for Immutable Value Objects**

```php
readonly class WorkflowContext
{
    public function __construct(
        public array $data,
        public array $metadata,
        public WorkflowInstance $instance,
        public DateTime $executedAt
    ) {}

    public function with(array $newData): self
    {
        return new self(
            data: array_merge($this->data, $newData),
            metadata: $this->metadata,
            instance: $this->instance,
            executedAt: $this->executedAt
        );
    }
}
```

## 🎯 Learning Curve Simplification

### 1. **Fluent Workflow Builder API**

#### Problem:
Current array-based definitions are verbose and error-prone.

#### Solution:
```php
// Instead of complex arrays
$workflow = WorkflowBuilder::create('user-registration')
    ->description('Complete user registration process')
    ->startWith(ValidateEmailAction::class)
    ->then(CreateUserAccountAction::class)
    ->then(SendWelcomeEmailAction::class)
    ->when('user.premium', function($builder) {
        $builder->then(SetupPremiumFeaturesAction::class);
    })
    ->onError(SendErrorNotificationAction::class)
    ->build();
```

### 2. **Convention-Based Configuration**

#### Current:
Developers need to manually configure everything.

#### Proposed:
```php
// Auto-discovery based on class naming
class UserRegistrationWorkflow extends Workflow
{
    // Steps are auto-discovered from methods prefixed with 'step'
    public function stepValidateEmail(): ActionResult { /* */ }
    
    public function stepCreateAccount(): ActionResult { /* */ }
    
    public function stepSendWelcome(): ActionResult { /* */ }
    
    // Conditions auto-discovered from 'when' prefix
    public function whenUserIsPremium(): bool { /* */ }
    
    // Error handlers from 'onError' prefix
    public function onErrorValidateEmail(): ActionResult { /* */ }
}
```

### 3. **Smart Default Actions**

```php
// Built-in common actions with smart defaults
WorkflowBuilder::create('order-processing')
    ->email(
        template: 'order-confirmation',
        to: '{{ order.customer.email }}',
        subject: 'Order Confirmation #{{ order.id }}'
    )
    ->delay(minutes: 5)
    ->http(
        url: 'https://api.payment.com/capture',
        method: 'POST',
        data: '{{ order.payment }}'
    )
    ->condition('payment.status === "success"')
    ->database(
        table: 'orders',
        action: 'update',
        where: ['id' => '{{ order.id }}'],
        data: ['status' => 'confirmed']
    );
```

### 4. **Visual Workflow Designer Integration**

```php
// Export workflows to visual format
$workflow->toMermaidDiagram(); // Generates Mermaid.js diagram
$workflow->toJson(); // JSON for visual editors
$workflow->toArray(); // Array for APIs

// Import from visual tools
$workflow = WorkflowBuilder::fromMermaid($mermaidString);
$workflow = WorkflowBuilder::fromJson($jsonDefinition);
```

### 5. **Better Error Messages & Debugging**

```php
// Enhanced error context with stack traces
class WorkflowException extends Exception
{
    public function __construct(
        string $message,
        public readonly WorkflowContext $context,
        public readonly Step $failedStep,
        public readonly array $workflowTrace = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
    
    public function getDebugInfo(): array
    {
        return [
            'workflow_id' => $this->context->instance->getId(),
            'failed_step' => $this->failedStep->getId(),
            'context_data' => $this->context->data,
            'execution_trace' => $this->workflowTrace,
        ];
    }
}
```

## 🏗️ Recommended Implementation Priority

### Phase 1: Core Type Safety
1. ✅ Enhanced ActionResult (already improved)
2. Readonly WorkflowContext
3. Better enum methods
4. Union types for action parameters

### Phase 2: Developer Experience
1. Fluent WorkflowBuilder API
2. Attribute-based configuration
3. Named arguments adoption
4. Smart default actions

### Phase 3: Advanced Features
1. Convention-based workflows
2. Visual designer integration
3. Enhanced debugging tools
4. Auto-discovery mechanisms

## 💡 Quick Wins for Immediate Impact

1. **Add WorkflowBuilder::quick() method** for common patterns
2. **Improve error messages** with context and suggestions
3. **Add more built-in actions** (HTTP, Database, Email, etc.)
4. **Create starter templates** for common workflows
5. **Add IDE autocompletion** support via better type hints

This analysis shows that while the library already uses some PHP 8.3+ features (readonly properties, enums), there's significant room for improvement in both modern PHP adoption and developer experience.
