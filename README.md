
# Workflow Mastery

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)

> **A powerful, framework-agnostic workflow engine for PHP with seamless Laravel integration**

**Workflow Mastery** transforms complex business processes into maintainable, executable workflows. Built with modern PHP 8.3+ features, it provides enterprise-grade workflow orchestration while remaining framework-agnostic at its core.

## ⚠️ Development Status

**This package is currently under active development and NOT READY FOR PRODUCTION USE.**

Features may be incomplete, APIs are subject to change, and breaking changes may occur. Please use only in development environments.

---

## ✨ Key Features

- 🚀 **Framework-Agnostic Core** - Use with Laravel, Symfony, or any PHP framework
- 🔄 **State Management** - Persistent workflow states with multiple storage adapters
- ⚡ **Parallel Execution** - Run multiple workflow steps simultaneously
- 🛡️ **Error Handling** - Built-in retry logic and compensation patterns
- 🎯 **Conditional Logic** - Dynamic workflow paths based on data conditions
- 📊 **Event System** - Comprehensive workflow lifecycle events
- 🔧 **Extensible Actions** - Easy to create custom workflow actions
- 📱 **Modern PHP** - Built with PHP 8.3+ features (readonly properties, enums, etc.)

---

## 📦 Installation

### Requirements

- **PHP**: 8.3 or higher
- **Laravel**: 10.x, 11.x, or 12.x (for Laravel integration)
- **Extensions**: `json`, `mbstring`

### Composer Installation

Install the package via Composer:

```bash
composer require solution-forest/workflow-mastery
```

### Laravel Integration

#### 1. Publish Configuration

```bash
php artisan vendor:publish --tag="workflow-mastery-config"
```

#### 2. Publish and Run Migrations

```bash
php artisan vendor:publish --tag="workflow-mastery-migrations"
php artisan migrate
```

#### 3. Optional: Publish Views

```bash
php artisan vendor:publish --tag="workflow-mastery-views"
```

#### 4. Environment Configuration

Add these variables to your `.env` file:

```env
# Workflow Storage Configuration
WORKFLOW_STORAGE_DRIVER=database
WORKFLOW_DB_CONNECTION=default

# Workflow Timeouts
WORKFLOW_DEFAULT_TIMEOUT="1 hour"
WORKFLOW_MAX_TIMEOUT="24 hours"

# Retry Configuration
WORKFLOW_DEFAULT_RETRY_ATTEMPTS=3
WORKFLOW_RETRY_DELAY="5 minutes"

# Event System
WORKFLOW_EVENTS_ENABLED=true
```

### Framework-Agnostic Usage

For non-Laravel projects, use the core engine directly:

```php
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Storage\FileStorage;
use SolutionForest\WorkflowMastery\Core\StateManager;

// Initialize components
$storage = new FileStorage('/path/to/workflows');
$stateManager = new StateManager($storage);
$engine = new WorkflowEngine($stateManager);

// Start a workflow
$workflowId = $engine->start('my-workflow', $workflowDefinition, $initialData);
```

---

## 🚀 Quick Start

### 1. Create Your First Action

```php
<?php

namespace App\Workflows\Actions;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class SendWelcomeEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        // Your business logic here
        Mail::to($data['email'])->send(new WelcomeEmail($data['user']));
        
        return ActionResult::success(['email_sent' => true, 'sent_at' => now()]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        $data = $context->getData();
        return !empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    }

    public function getName(): string
    {
        return 'Send Welcome Email';
    }

    public function getDescription(): string
    {
        return 'Sends a welcome email to the newly registered user';
    }
}
```

### 2. Define a Workflow

```php
<?php

use App\Workflows\Actions\SendWelcomeEmailAction;
use App\Workflows\Actions\CreateUserProfileAction;

$userRegistrationWorkflow = [
    'name' => 'User Registration Process',
    'version' => '1.0',
    'description' => 'Handles new user registration workflow',
    'steps' => [
        [
            'id' => 'send_welcome',
            'name' => 'Send Welcome Email',
            'action' => SendWelcomeEmailAction::class,
        ],
        [
            'id' => 'create_profile',
            'name' => 'Create User Profile',
            'action' => CreateUserProfileAction::class,
        ],
    ],
    'transitions' => [
        [
            'from' => 'send_welcome',
            'to' => 'create_profile',
            'condition' => 'email_sent == true'
        ],
    ],
];
```

### 3. Execute the Workflow

#### Using Laravel Facade

```php
use SolutionForest\WorkflowMastery\Facades\WorkflowMastery;

// In your controller
public function registerUser(Request $request)
{
    $user = User::create($request->validated());

    // Start the workflow
    $workflowInstance = WorkflowMastery::start($userRegistrationWorkflow, [
        'user_id' => $user->id,
        'email' => $user->email,
        'user' => $user->toArray(),
    ]);

    return response()->json([
        'user' => $user,
        'workflow_id' => $workflowInstance->getId(),
        'status' => $workflowInstance->getState()->value,
    ]);
}
```

#### Using Helper Functions

```php
// Start a workflow
$workflowId = start_workflow('user-registration', $userRegistrationWorkflow, $userData);

// Check workflow status
$status = get_workflow_status($workflowId);

// Resume a paused workflow
resume_workflow($workflowId);

// Cancel a workflow
cancel_workflow($workflowId);
```

---

## 💼 Real-World Examples

### E-commerce Order Processing

```php
$orderProcessingWorkflow = [
    'name' => 'E-commerce Order Processing',
    'steps' => [
        ['id' => 'validate_order', 'action' => ValidateOrderAction::class],
        ['id' => 'check_inventory', 'action' => CheckInventoryAction::class],
        ['id' => 'process_payment', 'action' => ProcessPaymentAction::class],
        ['id' => 'reserve_items', 'action' => ReserveInventoryAction::class],
        ['id' => 'create_shipment', 'action' => CreateShipmentAction::class],
        ['id' => 'send_confirmation', 'action' => SendOrderConfirmationAction::class],
    ],
    'transitions' => [
        ['from' => 'validate_order', 'to' => 'check_inventory', 'condition' => 'order.valid == true'],
        ['from' => 'check_inventory', 'to' => 'process_payment', 'condition' => 'inventory.available == true'],
        ['from' => 'process_payment', 'to' => 'reserve_items', 'condition' => 'payment.successful == true'],
        ['from' => 'reserve_items', 'to' => 'create_shipment'],
        ['from' => 'create_shipment', 'to' => 'send_confirmation'],
    ],
    'error_handling' => [
        'payment_failure' => [
            'compensation' => RefundAction::class,
            'retry_attempts' => 3,
        ],
        'inventory_shortage' => [
            'compensation' => ReleaseReservationAction::class,
            'notification' => NotifyCustomerAction::class,
        ],
    ],
];
```

### Document Approval Process

```php
$documentApprovalWorkflow = [
    'name' => 'Document Approval Process',
    'steps' => [
        ['id' => 'submit_document', 'action' => SubmitDocumentAction::class],
        ['id' => 'manager_review', 'action' => ManagerReviewAction::class, 'timeout' => '2 days'],
        ['id' => 'legal_review', 'action' => LegalReviewAction::class, 'timeout' => '5 days'],
        ['id' => 'compliance_check', 'action' => ComplianceCheckAction::class],
        ['id' => 'final_approval', 'action' => FinalApprovalAction::class],
        ['id' => 'archive_document', 'action' => ArchiveDocumentAction::class],
    ],
    'parallel_execution' => [
        ['manager_review', 'legal_review'], // These can run simultaneously
    ],
    'escalation' => [
        'timeout_action' => EscalateToSeniorManagerAction::class,
        'notification' => TimeoutNotificationAction::class,
    ],
];
```

---

## 🛠️ Laravel Developer Guide

### Working with Controllers

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SolutionForest\WorkflowMastery\Facades\WorkflowMastery;

class WorkflowController extends Controller
{
    public function startOrderProcessing(Request $request)
    {
        $order = Order::create($request->validated());
        
        $workflow = WorkflowMastery::start($this->getOrderWorkflow(), [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total_amount' => $order->total,
            'items' => $order->items->toArray(),
        ]);

        return response()->json([
            'order_id' => $order->id,
            'workflow_id' => $workflow->getId(),
            'status' => $workflow->getState()->value,
        ]);
    }

    public function getWorkflowStatus(string $workflowId)
    {
        try {
            $workflow = WorkflowMastery::getWorkflow($workflowId);
            
            return response()->json([
                'workflow_id' => $workflow->getId(),
                'state' => $workflow->getState()->value,
                'current_step' => $workflow->getCurrentStep()?->getId(),
                'progress_percentage' => $this->calculateProgress($workflow),
                'created_at' => $workflow->getCreatedAt(),
                'updated_at' => $workflow->getUpdatedAt(),
                'data' => $workflow->getData(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }
    }

    public function listWorkflows(Request $request)
    {
        $workflows = WorkflowMastery::listWorkflows([
            'state' => $request->get('state'),
            'limit' => $request->get('limit', 20),
            'offset' => $request->get('offset', 0),
        ]);

        return response()->json($workflows);
    }
}
```

### Queue Integration

For long-running workflows, integrate with Laravel's queue system:

```php
<?php

namespace App\Workflows\Actions;

use Illuminate\Support\Facades\Queue;
use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;

class ProcessLargeDatasetAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        // Dispatch heavy processing to queue
        $job = new ProcessDatasetJob($data['dataset_id'], $data['parameters']);
        Queue::push($job);
        
        return ActionResult::success([
            'status' => 'queued',
            'job_id' => $job->getId(),
            'estimated_duration' => '30 minutes',
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        $data = $context->getData();
        return !empty($data['dataset_id']) && !empty($data['parameters']);
    }

    public function getName(): string
    {
        return 'Process Large Dataset';
    }

    public function getDescription(): string
    {
        return 'Processes large datasets asynchronously using Laravel queues';
    }
}
```

### Event Listeners

Listen to workflow events for monitoring and logging:

```php
<?php

namespace App\Listeners;

use SolutionForest\WorkflowMastery\Events\WorkflowStarted;
use SolutionForest\WorkflowMastery\Events\WorkflowCompleted;
use SolutionForest\WorkflowMastery\Events\WorkflowFailed;

class WorkflowEventListener
{
    public function handleWorkflowStarted(WorkflowStarted $event): void
    {
        Log::info('Workflow started', [
            'workflow_id' => $event->workflowInstance->getId(),
            'definition_name' => $event->workflowInstance->getDefinition()->getName(),
        ]);
    }

    public function handleWorkflowCompleted(WorkflowCompleted $event): void
    {
        Log::info('Workflow completed successfully', [
            'workflow_id' => $event->workflowInstance->getId(),
            'duration' => $event->workflowInstance->getDuration(),
        ]);
        
        // Send notification, update metrics, etc.
    }

    public function handleWorkflowFailed(WorkflowFailed $event): void
    {
        Log::error('Workflow failed', [
            'workflow_id' => $event->workflowInstance->getId(),
            'error' => $event->error->getMessage(),
            'step' => $event->failedStep?->getId(),
        ]);
        
        // Send alert, trigger compensation, etc.
    }
}
```

### Custom Storage Adapter

Create a custom storage adapter for specific needs:

```php
<?php

namespace App\Workflows\Storage;

use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;

class RedisStorageAdapter implements StorageAdapter
{
    public function __construct(private \Redis $redis) {}

    public function save(WorkflowInstance $instance): void
    {
        $key = "workflow:{$instance->getId()}";
        $data = serialize($instance);
        $this->redis->setex($key, 86400, $data); // 24 hours TTL
    }

    public function load(string $id): WorkflowInstance
    {
        $key = "workflow:{$id}";
        $data = $this->redis->get($key);
        
        if (!$data) {
            throw new \InvalidArgumentException("Workflow not found: {$id}");
        }
        
        return unserialize($data);
    }

    public function exists(string $id): bool
    {
        return $this->redis->exists("workflow:{$id}") > 0;
    }

    public function delete(string $id): void
    {
        $this->redis->del("workflow:{$id}");
    }

    public function findInstances(array $criteria = []): array
    {
        // Implementation for finding workflows by criteria
        $pattern = "workflow:*";
        $keys = $this->redis->keys($pattern);
        
        $instances = [];
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data) {
                $instances[] = unserialize($data);
            }
        }
        
        return $this->filterByCriteria($instances, $criteria);
    }
}
```

---

## 🏗️ Architecture Overview

### Core Components

```
┌─────────────────────────────────────────┐
│           Laravel Integration           │ ← Framework-specific layer
├─────────────────────────────────────────┤
│         Workflow Engine Core           │ ← Pure PHP, framework-agnostic
│    • WorkflowEngine                     │
│    • WorkflowDefinition                 │
│    • WorkflowInstance                   │
│    • StateManager                       │
├─────────────────────────────────────────┤
│            Action System                │ ← Business logic encapsulation
│    • WorkflowAction Interface           │
│    • ActionResult                       │
│    • WorkflowContext                    │
├─────────────────────────────────────────┤
│        Storage Adapters                 │ ← Pluggable persistence
│    • DatabaseStorage                    │
│    • FileStorage                        │
│    • MemoryStorage (testing)            │
└─────────────────────────────────────────┘
```

### Design Principles

#### 🎯 Separation of Concerns
- **Workflow Definitions**: Declarative configuration (JSON/PHP arrays)
- **Business Logic**: Encapsulated in Action classes
- **State Management**: Handled by the engine core
- **Framework Integration**: Thin adapter layer

#### 🔄 Event-Driven Architecture
- **Lifecycle Events**: `WorkflowStarted`, `StepCompleted`, `WorkflowCompleted`, `WorkflowFailed`
- **Custom Hooks**: Pre/post execution hooks for each step
- **Observability**: Built-in logging, metrics, and monitoring support

#### 🧩 Extensible Design
Every component can be extended or replaced:
- Custom actions implementing `WorkflowAction`
- Custom storage adapters implementing `StorageAdapter`
- Custom event listeners for workflow events
- Custom condition evaluators for complex logic

---

## 🛡️ Advanced Patterns

### Compensation Actions (Saga Pattern)

```php
$orderWorkflow = [
    'steps' => [
        [
            'id' => 'reserve_inventory',
            'action' => ReserveInventoryAction::class,
            'compensation' => ReleaseInventoryAction::class,
        ],
        [
            'id' => 'charge_payment',
            'action' => ChargePaymentAction::class,
            'compensation' => RefundPaymentAction::class,
        ],
        [
            'id' => 'ship_order',
            'action' => ShipOrderAction::class,
            'compensation' => CancelShipmentAction::class,
        ],
    ],
    'error_handling' => [
        'execute_compensation' => true,
        'compensation_order' => 'reverse', // Execute in reverse order
    ],
];
```

### Conditional Branching

```php
$approvalWorkflow = [
    'steps' => [
        ['id' => 'check_amount', 'action' => CheckAmountAction::class],
        ['id' => 'manager_approval', 'action' => ManagerApprovalAction::class],
        ['id' => 'ceo_approval', 'action' => CEOApprovalAction::class],
        ['id' => 'auto_approve', 'action' => AutoApproveAction::class],
    ],
    'transitions' => [
        ['from' => 'check_amount', 'to' => 'auto_approve', 'condition' => 'amount < 1000'],
        ['from' => 'check_amount', 'to' => 'manager_approval', 'condition' => 'amount >= 1000 && amount < 10000'],
        ['from' => 'check_amount', 'to' => 'ceo_approval', 'condition' => 'amount >= 10000'],
        ['from' => 'manager_approval', 'to' => 'ceo_approval', 'condition' => 'amount >= 5000'],
    ],
];
```

### Parallel Execution

```php
$cicdWorkflow = [
    'steps' => [
        ['id' => 'build', 'action' => BuildAction::class],
        ['id' => 'unit_tests', 'action' => UnitTestsAction::class],
        ['id' => 'integration_tests', 'action' => IntegrationTestsAction::class],
        ['id' => 'security_scan', 'action' => SecurityScanAction::class],
        ['id' => 'deploy', 'action' => DeployAction::class],
    ],
    'parallel_groups' => [
        ['unit_tests', 'integration_tests', 'security_scan'], // Run in parallel after build
    ],
    'transitions' => [
        ['from' => 'build', 'to' => 'unit_tests'],
        ['from' => 'build', 'to' => 'integration_tests'],
        ['from' => 'build', 'to' => 'security_scan'],
        ['from' => 'unit_tests', 'to' => 'deploy'],
        ['from' => 'integration_tests', 'to' => 'deploy'],
        ['from' => 'security_scan', 'to' => 'deploy'],
    ],
    'join_conditions' => [
        'deploy' => 'all_completed', // Wait for all parallel tasks to complete
    ],
];
```

---

## 📝 Configuration

### Complete Configuration Reference

```php
<?php
// config/workflow-mastery.php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),
        'connection' => env('WORKFLOW_DB_CONNECTION', 'default'),
        'table' => env('WORKFLOW_TABLE', 'workflow_instances'),
        
        // File storage options
        'file_path' => env('WORKFLOW_FILE_PATH', storage_path('workflows')),
        
        // Redis storage options
        'redis_connection' => env('WORKFLOW_REDIS_CONNECTION', 'default'),
        'redis_prefix' => env('WORKFLOW_REDIS_PREFIX', 'workflow:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Configuration
    |--------------------------------------------------------------------------
    */
    'timeouts' => [
        'default' => env('WORKFLOW_DEFAULT_TIMEOUT', '1 hour'),
        'max' => env('WORKFLOW_MAX_TIMEOUT', '24 hours'),
        'step_specific' => [
            'email_action' => '5 minutes',
            'payment_processing' => '30 seconds',
            'approval_process' => '7 days',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retries' => [
        'default_attempts' => env('WORKFLOW_DEFAULT_RETRY_ATTEMPTS', 3),
        'delay' => env('WORKFLOW_RETRY_DELAY', '5 minutes'),
        'backoff_multiplier' => env('WORKFLOW_BACKOFF_MULTIPLIER', 2),
        'max_delay' => env('WORKFLOW_MAX_RETRY_DELAY', '1 hour'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event System
    |--------------------------------------------------------------------------
    */
    'events' => [
        'enabled' => env('WORKFLOW_EVENTS_ENABLED', true),
        'listeners' => [
            // Register your event listeners here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Monitoring
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'max_parallel_executions' => env('WORKFLOW_MAX_PARALLEL', 10),
        'memory_limit' => env('WORKFLOW_MEMORY_LIMIT', '256M'),
        'execution_time_limit' => env('WORKFLOW_TIME_LIMIT', 300), // seconds
    ],

    'monitoring' => [
        'metrics_enabled' => env('WORKFLOW_METRICS_ENABLED', true),
        'log_level' => env('WORKFLOW_LOG_LEVEL', 'info'),
        'slow_query_threshold' => env('WORKFLOW_SLOW_QUERY_THRESHOLD', 1000), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allowed_actions' => [
            // Whitelist allowed action classes for security
            // 'App\\Workflows\\Actions\\*',
        ],
        'encrypt_sensitive_data' => env('WORKFLOW_ENCRYPT_DATA', false),
        'audit_enabled' => env('WORKFLOW_AUDIT_ENABLED', true),
    ],
];
```

---

## 🧪 Testing

### Testing Workflows

```php
<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Storage\MemoryStorage;

class OrderProcessingWorkflowTest extends TestCase
{
    private WorkflowEngine $workflowEngine;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use in-memory storage for testing
        $storage = new MemoryStorage();
        $stateManager = new StateManager($storage);
        $this->workflowEngine = new WorkflowEngine($stateManager);
    }

    public function test_successful_order_processing()
    {
        // Arrange
        $order = Order::factory()->create(['total' => 150.00]);
        $workflow = $this->getOrderProcessingWorkflow();
        
        // Act
        $workflowId = $this->workflowEngine->start('order-' . $order->id, $workflow, [
            'order_id' => $order->id,
            'total' => $order->total,
            'customer_id' => $order->customer_id,
        ]);
        
        // Assert
        $instance = $this->workflowEngine->getWorkflow($workflowId);
        $this->assertEquals(WorkflowState::COMPLETED, $instance->getState());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processed',
        ]);
    }

    public function test_payment_failure_triggers_compensation()
    {
        // Mock payment service to fail
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('charge')
                ->once()
                ->andThrow(new PaymentException('Insufficient funds'));
        });
        
        $order = Order::factory()->create(['total' => 1000.00]);
        $workflowId = $this->startOrderWorkflow($order);
        
        $instance = $this->workflowEngine->getWorkflow($workflowId);
        
        $this->assertEquals(WorkflowState::FAILED, $instance->getState());
        $this->assertNotEmpty($instance->getCompensationActions());
    }

    public function test_high_value_order_requires_manager_approval()
    {
        $order = Order::factory()->create(['total' => 5000.00]);
        $workflowId = $this->startOrderWorkflow($order);
        
        $instance = $this->workflowEngine->getWorkflow($workflowId);
        
        $this->assertEquals(WorkflowState::WAITING, $instance->getState());
        $this->assertEquals('manager_approval', $instance->getCurrentStep()->getId());
    }

    private function getOrderProcessingWorkflow(): array
    {
        return [
            'name' => 'Order Processing',
            'steps' => [
                ['id' => 'validate', 'action' => ValidateOrderAction::class],
                ['id' => 'payment', 'action' => ProcessPaymentAction::class],
                ['id' => 'fulfill', 'action' => FulfillOrderAction::class],
            ],
            'transitions' => [
                ['from' => 'validate', 'to' => 'payment'],
                ['from' => 'payment', 'to' => 'fulfill'],
            ],
        ];
    }
}
```

### Testing Custom Actions

```php
<?php

namespace Tests\Unit\Workflows\Actions;

use Tests\TestCase;
use App\Workflows\Actions\SendEmailAction;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class SendEmailActionTest extends TestCase
{
    public function test_can_execute_with_valid_email()
    {
        $action = new SendEmailAction();
        $context = new WorkflowContext([
            'email' => 'test@example.com',
            'template' => 'welcome',
        ]);

        $this->assertTrue($action->canExecute($context));
    }

    public function test_cannot_execute_with_invalid_email()
    {
        $action = new SendEmailAction();
        $context = new WorkflowContext([
            'email' => 'invalid-email',
            'template' => 'welcome',
        ]);

        $this->assertFalse($action->canExecute($context));
    }

    public function test_execute_sends_email_successfully()
    {
        Mail::fake();
        
        $action = new SendEmailAction();
        $context = new WorkflowContext([
            'email' => 'test@example.com',
            'template' => 'welcome',
            'user_name' => 'John Doe',
        ]);

        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('email_sent', $result->getData());
        
        Mail::assertSent(WelcomeEmail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }
}
```

---

## 🚀 Performance & Best Practices

### Optimization Tips

#### 1. **Use Appropriate Storage**
```php
// For high-throughput: Use Redis or Memory storage
'storage' => ['driver' => 'redis']

// For persistence: Use database storage
'storage' => ['driver' => 'database']

// For testing: Use memory storage
'storage' => ['driver' => 'memory']
```

#### 2. **Optimize Action Design**
```php
// ✅ Good: Lightweight, focused actions
class ValidateOrderAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $orderId = $context->getData()['order_id'];
        $validator = app(OrderValidator::class);
        
        $isValid = $validator->validate($orderId);
        
        return ActionResult::success(['valid' => $isValid]);
    }
}

// ❌ Bad: Heavy actions that do too much
class ProcessEverythingAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Don't put all business logic in one action
        $this->validateOrder($context);
        $this->processPayment($context);
        $this->sendEmails($context);
        $this->updateInventory($context);
        // ... more logic
    }
}
```

#### 3. **Handle Large Data Sets**
```php
// ✅ Store references, not large objects
$workflowData = [
    'user_id' => 123,
    'order_id' => 456,
    'file_path' => '/storage/large-file.csv',
];

// ❌ Don't store large data in workflow context
$workflowData = [
    'users' => User::all()->toArray(), // Too much data
    'file_content' => file_get_contents('huge-file.csv'), // Memory issues
];
```

#### 4. **Use Queue Integration**
```php
class ProcessLargeFileAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Dispatch to queue for heavy processing
        ProcessFileJob::dispatch($context->getData()['file_id']);
        
        return ActionResult::success(['status' => 'queued']);
    }
}
```

### Common Pitfalls to Avoid

#### ❌ **Stateful Actions**
```php
// Don't store state in action instances
class BadAction implements WorkflowAction
{
    private int $counter = 0; // This won't work in distributed systems
    
    public function execute(WorkflowContext $context): ActionResult
    {
        $this->counter++; // State is lost between executions
        return ActionResult::success(['count' => $this->counter]);
    }
}

// ✅ Keep state in workflow context
class GoodAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        $count = ($data['counter'] ?? 0) + 1;
        
        return ActionResult::success(['counter' => $count]);
    }
}
```

#### ❌ **Blocking Operations**
```php
// Don't perform blocking operations in actions
class BadApiAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // This blocks the entire workflow
        sleep(30);
        $response = $this->callSlowApi();
        return ActionResult::success($response);
    }
}

// ✅ Use timeouts and async processing
class GoodApiAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        try {
            $response = Http::timeout(10)->get($apiUrl);
            return ActionResult::success($response->json());
        } catch (RequestException $e) {
            return ActionResult::retry('API timeout, will retry', [
                'retry_after' => 60,
                'max_attempts' => 3
            ]);
        }
    }
}
```

---

## 🌍 Comparison with Other Workflow Engines

| Feature | Workflow Mastery | Temporal | Laravel Workflow | Zeebe |
|---------|------------------|----------|------------------|--------|
| **Language** | PHP | Go/Java/Python | PHP | Java |
| **Framework** | Framework-agnostic | Framework-agnostic | Laravel-only | Framework-agnostic |
| **State Persistence** | ✅ Multiple adapters | ✅ Built-in | ✅ Database | ✅ Built-in |
| **Parallel Execution** | ✅ | ✅ | ❌ | ✅ |
| **Visual Designer** | 🔄 Planned | ✅ | ❌ | ✅ |
| **Compensation** | ✅ Saga pattern | ✅ | ❌ | ✅ |
| **Learning Curve** | Low | Medium | Low | High |
| **Cloud Native** | ✅ | ✅ | ❌ | ✅ |
| **Laravel Integration** | ✅ Native | ❌ | ✅ | ❌ |

---

## 🔗 Resources & Links

### Documentation
- [Full Documentation](https://docs.solutionforest.com/workflow-mastery)
- [API Reference](https://docs.solutionforest.com/workflow-mastery/api)
- [Migration Guide](https://docs.solutionforest.com/workflow-mastery/migration)

### Examples & Tutorials
- [Example Repository](https://github.com/solution-forest/workflow-mastery-examples)
- [Video Tutorial Series](https://youtube.com/solutionforest)
- [Blog Posts](https://blog.solutionforest.com/tags/workflow-mastery)

### Community
- [GitHub Discussions](https://github.com/solution-forest/workflow-mastery/discussions)
- [Discord Server](https://discord.gg/solutionforest)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/workflow-mastery)

---

## 📋 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/solution-forest/workflow-mastery.git
cd workflow-mastery

# Install dependencies
composer install

# Set up testing environment
cp .env.example .env.testing
php artisan key:generate --env=testing

# Run tests
composer test

# Run static analysis
composer phpstan

# Run code style checks
composer pint
```

## 🔒 Security

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 👥 Credits

- [Solution Forest Team](https://github.com/solution-forest)
- [Alan Lam](https://github.com/lam0819)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

<div align="center">

**Built with ❤️ by [Solution Forest](https://solutionforest.com)**

[![Solution Forest](https://solutionforest.com/images/logo.png)](https://solutionforest.com)

*Empowering developers to build better workflows*

</div>
        'check_inventory' => ['action' => CheckInventoryAction::class],
        'process_payment' => ['action' => ProcessPaymentAction::class],
        'fulfill_order' => ['action' => FulfillOrderAction::class],
        'send_confirmation' => ['action' => SendConfirmationAction::class],
    ],
    'transitions' => [
        ['from' => 'validate_order', 'to' => 'check_inventory', 'condition' => 'order.valid'],
        ['from' => 'check_inventory', 'to' => 'process_payment', 'condition' => 'inventory.available'],
        ['from' => 'process_payment', 'to' => 'fulfill_order', 'condition' => 'payment.successful'],
        ['from' => 'fulfill_order', 'to' => 'send_confirmation'],
    ],
];
```

### Document Approval Process
```php
$approvalWorkflow = [
    'name' => 'Document Approval',
    'steps' => [
        'submit_document' => ['action' => SubmitDocumentAction::class],
        'manager_review' => ['action' => ManagerReviewAction::class, 'timeout' => '2 days'],
        'legal_review' => ['action' => LegalReviewAction::class, 'timeout' => '5 days'],
        'final_approval' => ['action' => FinalApprovalAction::class],
        'archive_document' => ['action' => ArchiveDocumentAction::class],
    ],
    'parallel_steps' => [
        ['manager_review', 'legal_review'] // Both can happen simultaneously
    ],
];
```

### CI/CD Pipeline
```php
$cicdWorkflow = [
    'name' => 'Deployment Pipeline',
    'steps' => [
        'run_tests' => ['action' => RunTestsAction::class],
        'build_application' => ['action' => BuildAction::class],
        'security_scan' => ['action' => SecurityScanAction::class],
        'deploy_staging' => ['action' => DeployToStagingAction::class],
        'run_e2e_tests' => ['action' => E2ETestsAction::class],
        'deploy_production' => ['action' => DeployToProductionAction::class],
    ],
    'error_handling' => [
        'on_failure' => 'rollback_deployment',
        'notify' => ['slack', 'email'],
    ],
];
```

---

## 🏗️ Architecture & Design Philosophy

### Core Principles

#### 🔧 **Framework Agnostic Core**
```
┌─────────────────────────────────────────┐
│           Laravel Integration           │ ← Framework-specific layer
├─────────────────────────────────────────┤
│         Workflow Engine Core           │ ← Pure PHP, no dependencies
│    (Actions, Conditions, States)       │
├─────────────────────────────────────────┤
│        Storage Adapters               │ ← Pluggable persistence
│    (Database, File, Memory, Redis)     │
└─────────────────────────────────────────┘
```

#### 🎯 **Separation of Concerns**
- **Workflow Definitions**: Declarative YAML/JSON/PHP arrays
- **Business Logic**: Encapsulated in Action classes
- **State Management**: Handled by the engine core
- **Framework Integration**: Thin adapter layer

#### 🔄 **Event-Driven Architecture**
- **Workflow Events**: `WorkflowStarted`, `StepCompleted`, `WorkflowFailed`
- **Custom Hooks**: Pre/post execution hooks for each step
- **Observability**: Built-in logging, metrics, and monitoring

#### 🧩 **Extensible Design**
```php
interface WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult;
    public function canExecute(WorkflowContext $context): bool;
    public function getRequiredPermissions(): array;
}

interface WorkflowCondition
{
    public function evaluate(WorkflowContext $context): bool;
}

interface WorkflowStorage
{
    public function saveWorkflowState(WorkflowInstance $instance): void;
    public function loadWorkflowState(string $instanceId): WorkflowInstance;
}
```

---

## 📋 Workflow Engine Best Practices

### 1. **Idempotent Actions**
```php
class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');
        
        // Check if payment already processed (idempotency)
        if ($order->payment_status === 'completed') {
            return ActionResult::success('Payment already processed');
        }
        
        // Process payment...
        return ActionResult::success();
    }
}
```

### 2. **Compensating Actions (Saga Pattern)**
```php
$workflowWithCompensation = [
    'steps' => [
        'reserve_inventory' => [
            'action' => ReserveInventoryAction::class,
            'compensation' => ReleaseInventoryAction::class,
        ],
        'charge_payment' => [
            'action' => ChargePaymentAction::class,
            'compensation' => RefundPaymentAction::class,
        ],
    ],
];
```

### 3. **Conditional Branching**
```php
$conditionalWorkflow = [
    'steps' => [
        'check_user_type' => ['action' => CheckUserTypeAction::class],
        'premium_processing' => ['action' => PremiumProcessingAction::class],
        'standard_processing' => ['action' => StandardProcessingAction::class],
    ],
    'transitions' => [
        ['from' => 'check_user_type', 'to' => 'premium_processing', 'condition' => 'user.type == "premium"'],
        ['from' => 'check_user_type', 'to' => 'standard_processing', 'condition' => 'user.type == "standard"'],
    ],
];
```

### 4. **Parallel Execution**
```php
$parallelWorkflow = [
    'steps' => [
        'start' => ['action' => StartAction::class],
        'task_a' => ['action' => TaskAAction::class],
        'task_b' => ['action' => TaskBAction::class],
        'task_c' => ['action' => TaskCAction::class],
        'merge' => ['action' => MergeResultsAction::class],
    ],
    'parallel_groups' => [
        ['task_a', 'task_b', 'task_c'], // Execute in parallel
    ],
    'join_conditions' => [
        'merge' => 'all_completed', // Wait for all parallel tasks
    ],
];
```

---

## 🌍 Workflow Engines in Other Ecosystems

| Language/Platform | Popular Engines | Key Features |
|------------------|-----------------|--------------|
| **Java** | [Camunda](https://camunda.com/), [Activiti](https://www.activiti.org/) | BPMN 2.0, Visual Designer, Enterprise Features |
| **C#/.NET** | [Elsa Workflows](https://elsa-workflows.github.io/elsa-core/), [Workflow Core](https://github.com/danielgerlag/workflow-core) | .NET Integration, Visual Designer |
| **Python** | [Airflow](https://airflow.apache.org/), [Prefect](https://www.prefect.io/) | DAG-based, Data Pipeline Focus |
| **JavaScript** | [Temporal](https://temporal.io/), [Zeebe](https://zeebe.io/) | Cloud-native, Microservices |
| **Go** | [Conductor](https://github.com/netflix/conductor), [Cadence](https://cadenceworkflow.io/) | High Performance, Distributed |
| **Rust** | [rusty-workflow](https://github.com/whatisinternet/rusty-workflow) | Memory Safety, Performance |

Our PHP workflow engine brings enterprise-grade features found in these mature ecosystems to the PHP community.

---

## 📦 Installation

Install the package via Composer:

```bash
composer require solution-forest/workflow-mastery
```

### Laravel Setup

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="workflow-engine-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="workflow-engine-config"
```

Optionally, publish the views:

```bash
php artisan vendor:publish --tag="workflow-engine-views"
```

### Framework-Agnostic Usage

For non-Laravel projects, you can use the core engine directly:

```php
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Storage\FileStorage;

$engine = new WorkflowEngine(new FileStorage('/path/to/workflows'));
$result = $engine->execute($workflowDefinition, $initialData);
```

---

## 🚀 Quick Start for Laravel Developers

### 1. Create Your First Action

Actions are the building blocks of workflows. Create a simple action:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        // Send welcome email
        Mail::to($data['email'])->send(new WelcomeEmail($data['user']));
        
        return ActionResult::success(['email_sent' => true]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        $data = $context->getData();
        return !empty($data['email']) && !empty($data['user']);
    }

    public function getName(): string
    {
        return 'Send Welcome Email';
    }

    public function getDescription(): string
    {
        return 'Sends a welcome email to the user';
    }
}
```

### 2. Define a Simple Workflow

Create a workflow configuration array:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use App\Actions\SendWelcomeEmailAction;
use App\Actions\CreateUserProfileAction;

class UserController extends Controller
{
    public function __construct(private WorkflowEngine $workflowEngine) {}

    public function register(Request $request)
    {
        // Create user first
        $user = User::create($request->validated());

        // Define workflow
        $workflow = [
            'name' => 'User Registration Process',
            'version' => '1.0',
            'steps' => [
                [
                    'id' => 'send_welcome',
                    'name' => 'Send Welcome Email',
                    'action' => SendWelcomeEmailAction::class,
                ],
                [
                    'id' => 'create_profile',
                    'name' => 'Create User Profile',
                    'action' => CreateUserProfileAction::class,
                ],
            ],
            'transitions' => [
                ['from' => 'send_welcome', 'to' => 'create_profile'],
            ],
        ];

        // Start workflow
        $workflowId = $this->workflowEngine->start(
            "user-registration-{$user->id}",
            $workflow,
            [
                'user_id' => $user->id,
                'email' => $user->email,
                'user' => $user->toArray(),
            ]
        );

        return response()->json([
            'user' => $user,
            'workflow_id' => $workflowId,
            'message' => 'Registration started successfully'
        ]);
    }
}
```

### 3. Using Helper Functions

Laravel Workflow Engine provides convenient helper functions:

```php
// Start a workflow
$workflowId = start_workflow('order-123', $orderWorkflow, $orderData);

// Get workflow status
$workflow = get_workflow($workflowId);
$status = $workflow->getState(); // PENDING, RUNNING, COMPLETED, FAILED, CANCELLED

// Resume a paused workflow
$workflow = resume_workflow($workflowId);

// Cancel a workflow
$workflow = cancel_workflow($workflowId);
```

### 4. Working with Queues

For long-running workflows, use Laravel's queue system:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;
use Illuminate\Support\Facades\Queue;

class ProcessLargeFileAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        // Dispatch to queue for heavy processing
        Queue::push(new ProcessFileJob($data['file_path']));
        
        return ActionResult::success([
            'status' => 'queued',
            'job_id' => 'process-' . uniqid(),
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return !empty($context->getData()['file_path']);
    }

    public function getName(): string { return 'Process Large File'; }
    public function getDescription(): string { return 'Processes large files asynchronously'; }
}
```

### 5. Configuration

Configure the workflow engine in `config/workflow-engine.php`:

```php
<?php

return [
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),
        'connection' => env('WORKFLOW_DB_CONNECTION', 'default'),
        'table' => 'workflow_instances',
    ],
    
    'events' => [
        'enabled' => true,
        'listeners' => [
            // Add your event listeners here
        ],
    ],
    
    'timeouts' => [
        'default' => '1 hour',
        'max' => '24 hours',
    ],
    
    'retries' => [
        'default_attempts' => 3,
        'delay' => '5 minutes',
    ],
];
```

### 6. Monitoring Workflows

Monitor workflow execution in your controllers:

```php
<?php

namespace App\Http\Controllers;

class WorkflowController extends Controller
{
    public function status($workflowId)
    {
        $status = workflow()->getStatus($workflowId);
        
        return response()->json([
            'workflow_id' => $status['workflow_id'],
            'state' => $status['state'],
            'progress' => $status['progress'] . '%',
            'current_step' => $status['current_step'],
            'created_at' => $status['created_at'],
            'updated_at' => $status['updated_at'],
        ]);
    }
    
    public function list(Request $request)
    {
        $workflows = workflow()->listWorkflows([
            'state' => $request->get('state'), // PENDING, RUNNING, etc.
            'limit' => $request->get('limit', 10),
        ]);
        
        return response()->json($workflows);
    }
}
```

### 7. Simple E-commerce Example

Here's a complete example for order processing:

```php
// app/Actions/ValidateOrderAction.php
class ValidateOrderAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData()['order'];
        
        // Validate order data
        if (empty($order['items']) || $order['total'] <= 0) {
            return ActionResult::failure('Invalid order data');
        }
        
        return ActionResult::success(['validated' => true]);
    }
    
    public function canExecute(WorkflowContext $context): bool
    {
        return !empty($context->getData()['order']);
    }
    
    public function getName(): string { return 'Validate Order'; }
    public function getDescription(): string { return 'Validates order data'; }
}

// app/Http/Controllers/OrderController.php
class OrderController extends Controller
{
    public function process(Request $request)
    {
        $order = Order::create($request->validated());
        
        $workflow = [
            'name' => 'Order Processing',
            'steps' => [
                ['id' => 'validate', 'action' => ValidateOrderAction::class],
                ['id' => 'payment', 'action' => ProcessPaymentAction::class],
                ['id' => 'fulfill', 'action' => FulfillOrderAction::class],
            ],
            'transitions' => [
                ['from' => 'validate', 'to' => 'payment'],
                ['from' => 'payment', 'to' => 'fulfill'],
            ],
        ];
        
        $workflowId = start_workflow("order-{$order->id}", $workflow, [
            'order' => $order->toArray(),
            'customer_id' => $order->customer_id,
        ]);
        
        return response()->json([
            'order_id' => $order->id,
            'workflow_id' => $workflowId,
            'status' => 'processing'
        ]);
    }
}
```

---

## 🎮 Usage Examples

### Basic Workflow Execution

```php
use SolutionForest\WorkflowEngine\Facades\WorkflowEngine;

// Define your workflow
$workflow = [
    'name' => 'User Onboarding',
    'version' => '1.0',
    'steps' => [
        'send_welcome_email' => [
            'action' => SendWelcomeEmailAction::class,
            'retry_attempts' => 3,
        ],
        'create_user_profile' => [
            'action' => CreateUserProfileAction::class,
        ],
        'assign_default_permissions' => [
            'action' => AssignPermissionsAction::class,
        ],
    ],
    'transitions' => [
        ['from' => 'send_welcome_email', 'to' => 'create_user_profile'],
        ['from' => 'create_user_profile', 'to' => 'assign_default_permissions'],
    ],
];

// Execute the workflow
$instance = WorkflowEngine::start($workflow, [
    'user_id' => 123,
    'email' => 'user@example.com',
]);

// Monitor progress
$status = WorkflowEngine::getStatus($instance->getId());
echo "Workflow Status: {$status->getState()}";
```

### Advanced Features

```php
// Workflow with error handling and retries
$robustWorkflow = [
    'name' => 'Data Processing Pipeline',
    'error_handling' => [
        'default_retry_attempts' => 3,
        'retry_delay' => '30 seconds',
        'on_failure' => 'send_alert',
    ],
    'steps' => [
        'validate_data' => [
            'action' => ValidateDataAction::class,
            'timeout' => '5 minutes',
        ],
        'process_data' => [
            'action' => ProcessDataAction::class,
            'timeout' => '30 minutes',
            'retry_attempts' => 5,
        ],
        'generate_report' => [
            'action' => GenerateReportAction::class,
        ],
    ],
    'webhooks' => [
        'on_completion' => 'https://api.example.com/workflow-completed',
        'on_failure' => 'https://api.example.com/workflow-failed',
    ],
];
```

### Custom Actions

```php
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\WorkflowContext;
use SolutionForest\WorkflowEngine\ActionResult;

class SendEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $email = $context->getData('email');
        $template = $context->getStepData('template', 'default');
        
        try {
            // Your email sending logic here
            Mail::to($email)->send(new WorkflowEmail($template, $context->getAllData()));
            
            return ActionResult::success([
                'email_sent' => true,
                'sent_at' => now(),
            ]);
        } catch (Exception $e) {
            return ActionResult::failure($e->getMessage(), [
                'email' => $email,
                'error_code' => $e->getCode(),
            ]);
        }
    }
    
    public function canExecute(WorkflowContext $context): bool
    {
        return !empty($context->getData('email'));
    }
    
    public function getRequiredPermissions(): array
    {
        return ['workflow.send_email'];
    }
}
```

---

## 🔧 Configuration

The workflow engine is highly configurable. Here's the default configuration:

```php
// config/workflow-engine.php
return [
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),
        'drivers' => [
            'database' => [
                'connection' => env('WORKFLOW_DB_CONNECTION'),
                'table' => 'workflow_instances',
            ],
            'file' => [
                'path' => storage_path('workflows'),
            ],
            'redis' => [
                'connection' => env('WORKFLOW_REDIS_CONNECTION', 'default'),
                'prefix' => 'workflow:',
            ],
        ],
    ],
    
    'execution' => [
        'timeout' => env('WORKFLOW_DEFAULT_TIMEOUT', 300), // 5 minutes
        'max_retries' => env('WORKFLOW_MAX_RETRIES', 3),
        'retry_delay' => env('WORKFLOW_RETRY_DELAY', 60), // 1 minute
    ],
    
    'monitoring' => [
        'enabled' => env('WORKFLOW_MONITORING_ENABLED', true),
        'log_channel' => env('WORKFLOW_LOG_CHANNEL', 'default'),
        'metrics' => [
            'enabled' => env('WORKFLOW_METRICS_ENABLED', false),
            'driver' => env('WORKFLOW_METRICS_DRIVER', 'prometheus'),
        ],
    ],
    
    'queue' => [
        'enabled' => env('WORKFLOW_QUEUE_ENABLED', true),
        'connection' => env('WORKFLOW_QUEUE_CONNECTION', 'default'),
        'queue' => env('WORKFLOW_QUEUE_NAME', 'workflows'),
    ],
];
```

---

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test:coverage
```

Run static analysis:

```bash
composer analyse
```

---

## � Best Practices & Tips for Laravel Developers

### Action Development Tips

```php
// ✅ Good: Keep actions focused and testable
class SendEmailAction implements WorkflowAction
{
    public function __construct(
        private MailService $mailService,
        private Logger $logger
    ) {}

    public function execute(WorkflowContext $context): ActionResult
    {
        try {
            $data = $context->getData();
            $this->mailService->send($data['template'], $data['recipient']);
            
            $this->logger->info('Email sent successfully', ['recipient' => $data['recipient']]);
            
            return ActionResult::success(['sent_at' => now()]);
        } catch (Exception $e) {
            $this->logger->error('Email failed', ['error' => $e->getMessage()]);
            return ActionResult::failure($e->getMessage());
        }
    }
}

// ❌ Avoid: Heavy logic in actions - delegate to services
class BadEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Don't put all your business logic here
        // Delegate to services instead
    }
}
```

### Workflow Design Patterns

```php
// Pattern 1: Sequential Processing
$sequentialWorkflow = [
    'steps' => [
        ['id' => 'step1', 'action' => Action1::class],
        ['id' => 'step2', 'action' => Action2::class],
        ['id' => 'step3', 'action' => Action3::class],
    ],
    'transitions' => [
        ['from' => 'step1', 'to' => 'step2'],
        ['from' => 'step2', 'to' => 'step3'],
    ],
];

// Pattern 2: Conditional Branching
$conditionalWorkflow = [
    'steps' => [
        ['id' => 'check_value', 'action' => CheckValueAction::class],
        ['id' => 'high_value_path', 'action' => HighValueAction::class],
        ['id' => 'normal_path', 'action' => NormalAction::class],
    ],
    'transitions' => [
        ['from' => 'check_value', 'to' => 'high_value_path', 'condition' => 'value > 1000'],
        ['from' => 'check_value', 'to' => 'normal_path', 'condition' => 'value <= 1000'],
    ],
];

// Pattern 3: Parallel Processing
$parallelWorkflow = [
    'steps' => [
        ['id' => 'start', 'action' => StartAction::class],
        ['id' => 'task_a', 'action' => TaskAAction::class],
        ['id' => 'task_b', 'action' => TaskBAction::class],
        ['id' => 'merge', 'action' => MergeAction::class],
    ],
    'transitions' => [
        ['from' => 'start', 'to' => 'task_a'],
        ['from' => 'start', 'to' => 'task_b'],
        ['from' => 'task_a', 'to' => 'merge'],
        ['from' => 'task_b', 'to' => 'merge'],
    ],
];
```

### Error Handling Strategies

```php
// Strategy 1: Compensation Actions
class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        
        try {
            $payment = PaymentService::charge($data['amount'], $data['card']);
            return ActionResult::success(['payment_id' => $payment->id]);
        } catch (PaymentException $e) {
            // Mark for compensation
            return ActionResult::failure($e->getMessage(), [
                'compensation_action' => RefundAction::class,
                'compensation_data' => ['charge_id' => $payment->id ?? null]
            ]);
        }
    }
}

// Strategy 2: Retry with Backoff
class ExternalApiAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        try {
            $response = Http::timeout(30)->get($context->getData()['api_url']);
            return ActionResult::success($response->json());
        } catch (RequestException $e) {
            if ($e->getCode() >= 500) {
                // Retryable error
                return ActionResult::retry('Server error, will retry', [
                    'retry_after' => 60, // seconds
                    'max_attempts' => 3
                ]);
            }
            
            return ActionResult::failure('Client error: ' . $e->getMessage());
        }
    }
}
```

### Testing Workflows

```php
// tests/Feature/OrderWorkflowTest.php
class OrderWorkflowTest extends TestCase
{
    public function test_successful_order_processing()
    {
        // Arrange
        $order = Order::factory()->create();
        $workflow = app(WorkflowEngine::class);
        
        // Act
        $workflowId = $workflow->start('order-process', [
            'name' => 'Order Processing',
            'steps' => [
                ['id' => 'validate', 'action' => ValidateOrderAction::class],
                ['id' => 'payment', 'action' => ProcessPaymentAction::class],
            ],
            'transitions' => [
                ['from' => 'validate', 'to' => 'payment'],
            ],
        ], ['order_id' => $order->id]);
        
        // Assert
        $instance = $workflow->getInstance($workflowId);
        $this->assertEquals('completed', $instance->getState()->value);
    }

    public function test_payment_failure_triggers_compensation()
    {
        // Mock payment failure
        PaymentService::shouldReceive('charge')
            ->once()
            ->andThrow(new PaymentException('Card declined'));
        
        // Test compensation logic
        $workflowId = $this->startOrderWorkflow();
        $instance = $this->workflowEngine->getInstance($workflowId);
        
        $this->assertEquals('failed', $instance->getState()->value);
        $this->assertNotEmpty($instance->getCompensationActions());
    }
}
```

### Performance Optimization

```php
// Use database transactions for consistency
class DatabaseActionBase implements WorkflowAction
{
    protected function executeInTransaction(callable $callback): ActionResult
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}

// Implement bulk operations
class BulkNotificationAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $users = $context->getData()['users'];
        
        // Process in chunks to avoid memory issues
        collect($users)->chunk(100)->each(function ($chunk) {
            Notification::send($chunk, new WorkflowNotification());
        });
        
        return ActionResult::success(['notified_count' => count($users)]);
    }
}
```

### Configuration Management

```php
// Use environment-specific configurations
// config/workflow-engine.php
return [
    'storage' => [
        'driver' => env('WORKFLOW_STORAGE_DRIVER', 'database'),
    ],
    
    'timeouts' => [
        'default' => env('WORKFLOW_DEFAULT_TIMEOUT', '1 hour'),
        'payment' => env('WORKFLOW_PAYMENT_TIMEOUT', '5 minutes'),
        'approval' => env('WORKFLOW_APPROVAL_TIMEOUT', '2 days'),
    ],
    
    'retries' => [
        'api_calls' => env('WORKFLOW_API_RETRY_ATTEMPTS', 3),
        'email_sending' => env('WORKFLOW_EMAIL_RETRY_ATTEMPTS', 2),
    ],
    
    'notifications' => [
        'slack_webhook' => env('WORKFLOW_SLACK_WEBHOOK'),
        'email_alerts' => env('WORKFLOW_EMAIL_ALERTS', 'admin@company.com'),
    ],
];
```

### Common Pitfalls to Avoid

```php
// ❌ Don't store large data in workflow context
$badContext = [
    'user_data' => User::with('orders', 'preferences', 'history')->find(1)->toArray(), // Too much data
    'large_file_content' => file_get_contents('huge-file.pdf'), // Memory issues
];

// ✅ Store references instead
$goodContext = [
    'user_id' => 1,
    'file_path' => '/storage/files/huge-file.pdf',
    'reference_id' => 'REF-123',
];

// ❌ Don't make actions stateful
class BadStatefulAction implements WorkflowAction
{
    private $counter = 0; // This won't work in distributed systems
    
    public function execute(WorkflowContext $context): ActionResult
    {
        $this->counter++; // State is lost between executions
        return ActionResult::success(['count' => $this->counter]);
    }
}

// ✅ Keep state in the workflow context
class GoodStatelessAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $data = $context->getData();
        $count = ($data['counter'] ?? 0) + 1;
        
        return ActionResult::success(['counter' => $count]);
    }
}
```

---

## �📚 Documentation

- [Full Documentation](https://docs.solution-forest.com/workflow-engine)
- [API Reference](https://docs.solution-forest.com/workflow-engine/api)
- [Examples Repository](https://github.com/solution-forest/workflow-engine-examples)
- [Video Tutorials](https://youtube.com/solution-forest)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alan](https://github.com/lam0819)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
