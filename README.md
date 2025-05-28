
# Workflow Mastery

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)

## ⚠️ WARNING: DEVELOPMENT STATUS

**This package is currently under active development and is NOT READY FOR PRODUCTION USE.**

Features may be incomplete, APIs might change, and there could be breaking changes. Use at your own risk in development environments only.

---

Inspired by the best ideas from many languages and platforms, **Workflow Mastery** aims to be a universal, modular, and extensible workflow engine. While it integrates seamlessly with Laravel, its core logic is designed to be framework-agnostic, so you can use it anywhere.

---

## 🎯 What is a Workflow Engine?

A **workflow engine** is a sophisticated software component that orchestrates and executes defined sequences of tasks, called workflows. It enables you to:

- **Model Complex Business Processes**: Transform business logic into executable, visual workflows
- **Automate Repetitive Tasks**: Reduce manual intervention and human error
- **Coordinate System Integration**: Seamlessly connect different services and modules
- **Ensure Process Consistency**: Guarantee that business rules are followed every time
- **Enable Process Evolution**: Adapt to changing requirements without code modifications

### 🌟 Why Choose Our Workflow Engine?

**Framework-Agnostic Core**: While this package provides seamless Laravel integration, the core workflow engine is completely independent of Laravel or any specific framework. This means:

- ✅ **Pure PHP Implementation**: Core business logic written in vanilla PHP
- ✅ **Universal Compatibility**: Use with Symfony, CodeIgniter, or any PHP framework
- ✅ **Standalone Usage**: Deploy in legacy systems or microservices
- ✅ **Easy Testing**: Unit test workflow logic without framework dependencies
- ✅ **Future-Proof**: Migrate between frameworks without rewriting workflows

---

## 🚀 Real-World Use Cases

### E-commerce Order Processing
```php
$orderWorkflow = [
    'name' => 'Order Processing Pipeline',
    'steps' => [
        'validate_order' => ['action' => ValidateOrderAction::class],
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
