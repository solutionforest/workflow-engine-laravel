
# Workflow Mastery

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)

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

## 📚 Documentation

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
