# Laravel Workflow Engine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-engine-laravel.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-engine-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solutionforest/workflow-engine-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solutionforest/workflow-engine-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-engine-laravel.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-engine-laravel)

**A modern, type-safe workflow engine for Laravel built with PHP 8.3+ features**

Create powerful business workflows with simple, maintainable code.

## ✨ Why Choose This Workflow Engine?

- 🎨 **Simple & Intuitive** - Array-based workflow definitions and fluent WorkflowBuilder API
- 🏷️ **Modern PHP 8.3+ Attributes** - Declarative configuration with #[WorkflowStep], #[Retry], #[Timeout] 
- 🔒 **Type Safety First** - Built with enums, strong typing, and modern PHP features
- ⚡ **Laravel Native** - Seamless integration with Laravel's ecosystem and helpers
- 🧩 **Extensible** - Easy to extend with custom actions and storage adapters
- 📚 **Well Tested** - Comprehensive test suite with real-world examples

---

## 🚀 Quick Start

### Installation

```bash
composer require solution-forest/workflow-engine-laravel
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="workflow-engine-config"
```

For database storage, run the migrations:

```bash
php artisan migrate
```

The migration will create a `workflow_instances` table to store workflow state and progress.

### Your First Workflow in 30 Seconds

```php
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;

// Create a simple workflow definition
$definition = [
    'name' => 'User Onboarding',
    'version' => '1.0',
    'steps' => [
        [
            'id' => 'welcome',
            'name' => 'Send Welcome',
            'action' => 'log',
            'parameters' => [
                'message' => 'Welcome {{ user.name }}!',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'setup',
            'name' => 'Setup Account', 
            'action' => 'log',
            'parameters' => [
                'message' => 'Setting up account for {{ user.email }}',
                'level' => 'info'
            ]
        ]
    ]
];

// Start the workflow using the engine
$workflowId = workflow()->start('user-onboarding-001', $definition, [
    'user' => ['name' => 'John Doe', 'email' => 'john@example.com']
]);

// Or use the helper functions
$workflowId = start_workflow('user-onboarding-002', $definition, [
    'user' => ['name' => 'Jane Doe', 'email' => 'jane@example.com']
]);
```

## 💼 Real-World Examples

### E-commerce Order Processing

```php
$definition = [
    'name' => 'Order Processing',
    'version' => '1.0',
    'steps' => [
        [
            'id' => 'validate-order',
            'name' => 'Validate Order',
            'action' => 'log',
            'parameters' => [
                'message' => 'Validating order {{ order.id }}',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'process-payment',
            'name' => 'Process Payment',
            'action' => 'log',
            'parameters' => [
                'message' => 'Processing payment for {{ order.total }}',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'fulfill-order',
            'name' => 'Fulfill Order',
            'action' => 'log',
            'parameters' => [
                'message' => 'Order {{ order.id }} fulfilled',
                'level' => 'info'
            ]
        ]
    ]
];

$workflowId = start_workflow('order-001', $definition, [
    'order' => ['id' => 'ORD-001', 'total' => 99.99]
]);
```

### Document Approval Process

```php
$definition = [
    'name' => 'Document Approval',
    'version' => '1.0',
    'steps' => [
        [
            'id' => 'submit',
            'name' => 'Submit Document',
            'action' => 'log',
            'parameters' => [
                'message' => 'Document {{ document.id }} submitted by {{ user.name }}',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'review',
            'name' => 'Manager Review',
            'action' => 'log',
            'parameters' => [
                'message' => 'Document {{ document.id }} under review',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'approve',
            'name' => 'Final Approval',
            'action' => 'log',
            'parameters' => [
                'message' => 'Document {{ document.id }} approved',
                'level' => 'info'
            ]
        ]
    ]
];

$workflowId = start_workflow('doc-approval-001', $definition, [
    'document' => ['id' => 'DOC-001'],
    'user' => ['name' => 'John Doe']
]);
```

### User Onboarding

```php
$definition = [
    'name' => 'User Onboarding',
    'version' => '1.0',
    'steps' => [
        [
            'id' => 'welcome',
            'name' => 'Send Welcome Message',
            'action' => 'log',
            'parameters' => [
                'message' => 'Welcome {{ user.name }}! Starting onboarding...',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'setup-profile',
            'name' => 'Setup User Profile',
            'action' => 'log',
            'parameters' => [
                'message' => 'Setting up profile for {{ user.email }}',
                'level' => 'info'
            ]
        ],
        [
            'id' => 'complete',
            'name' => 'Complete Onboarding',
            'action' => 'log',
            'parameters' => [
                'message' => 'Onboarding complete for {{ user.name }}',
                'level' => 'info'
            ]
        ]
    ]
];

$workflowId = start_workflow('onboarding-001', $definition, [
    'user' => ['name' => 'Jane Doe', 'email' => 'jane@example.com']
]);
```

### Creating Custom Actions

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

class SendEmailAction implements WorkflowAction
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function execute(WorkflowContext $context): ActionResult
    {
        $to = $this->config['to'] ?? 'default@example.com';
        $subject = $this->config['subject'] ?? 'Notification';
        
        // Process template variables
        $processedTo = $this->processTemplate($to, $context->getData());
        $processedSubject = $this->processTemplate($subject, $context->getData());
        
        // Send email using Laravel's Mail facade
        Mail::to($processedTo)->send(new WorkflowNotification($processedSubject));
        
        return ActionResult::success([
            'email_sent' => true,
            'recipient' => $processedTo
        ]);
    }
    
    private function processTemplate(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($data) {
            $key = trim($matches[1]);
            return data_get($data, $key, $matches[0]);
        }, $template);
    }
}
```

Then use it in your workflows:

```php
$definition = [
    'name' => 'Email Workflow',
    'steps' => [
        [
            'id' => 'send-email',
            'action' => SendEmailAction::class,
            'parameters' => [
                'to' => '{{ user.email }}',
                'subject' => 'Welcome {{ user.name }}!'
            ]
        ]
    ]
];
```

## 🔧 Core Features

### Modern PHP 8.3+ Attributes

Enhance your workflow actions with declarative attributes for configuration:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Attributes\Timeout;
use SolutionForest\WorkflowEngine\Attributes\Retry;
use SolutionForest\WorkflowEngine\Attributes\Condition;
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

#[WorkflowStep(
    id: 'send_email',
    name: 'Send Welcome Email',
    description: 'Sends a welcome email to new users'
)]
#[Timeout(minutes: 5)]
#[Retry(attempts: 3, backoff: 'exponential')]
#[Condition('user.email is not null')]
class SendWelcomeEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $user = $context->getData('user');
        
        // Send email logic here
        Mail::to($user['email'])->send(new WelcomeEmail($user));
        
        return ActionResult::success(['email_sent' => true]);
    }
}
```

#### Available Attributes:

- **`#[WorkflowStep]`** - Define step metadata (id, name, description, config)
- **`#[Timeout]`** - Set execution timeouts (seconds, minutes, hours)  
- **`#[Retry]`** - Configure retry behavior (attempts, backoff strategy, delays)
- **`#[Condition]`** - Add conditional execution rules

### WorkflowBuilder Fluent API

Create workflows with an intuitive, chainable API:

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;

$workflow = WorkflowBuilder::create('user-onboarding')
    ->description('Complete user onboarding process')
    ->addStep('welcome', SendWelcomeEmailAction::class)
    ->addStep('setup', SetupUserAction::class, ['template' => 'premium'])
    ->when('user.plan === "premium"', function($builder) {
        $builder->addStep('premium-setup', PremiumSetupAction::class);
    })
    ->email('tips-email', '{{ user.email }}', 'Getting Started Tips')
    ->delay(hours: 24)
    ->addStep('complete', CompleteOnboardingAction::class)
    ->build();

// Start the workflow
$workflowId = $workflow->start('user-001', [
    'user' => ['email' => 'john@example.com', 'plan' => 'premium']
]);
```

### Modern PHP 8.3+ Enums

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

// Rich, type-safe workflow states
$state = WorkflowState::RUNNING;
echo $state->color();     // 'blue'
echo $state->icon();      // '▶️'
echo $state->label();     // 'Running'

// Smart state transitions
if ($state->canTransitionTo(WorkflowState::COMPLETED)) {
    workflow()->complete($workflowId);
}
```

### Template Processing

```php
// Use template variables in your workflow steps
$definition = [
    'name' => 'User Notification',
    'steps' => [
        [
            'id' => 'notify',
            'action' => 'log',
            'parameters' => [
                'message' => 'Hello {{ user.name }}, your order {{ order.id }} is ready!',
                'level' => 'info'
            ]
        ]
    ]
];

start_workflow('notification-001', $definition, [
    'user' => ['name' => 'John'],
    'order' => ['id' => 'ORD-123']
]);
```

### Built-in Actions

```php
// Log Action - Built-in logging with template support
[
    'id' => 'log-step',
    'action' => 'log',
    'parameters' => [
        'message' => 'Processing {{ item.name }}',
        'level' => 'info'
    ]
]

// Delay Action - Built-in delays (for testing/demo)
[
    'id' => 'delay-step', 
    'action' => 'delay',
    'parameters' => [
        'seconds' => 2
    ]
]
```

### Workflow Management

```php
// Start workflows
$workflowId = start_workflow('my-workflow', $definition, $context);

// Get workflow status
$instance = get_workflow($workflowId);
echo $instance->getState()->label(); // Current state

// List all workflows
$workflows = list_workflows();

// Filter workflows by state
$runningWorkflows = list_workflows(['state' => WorkflowState::RUNNING]);

// Cancel a workflow
cancel_workflow($workflowId, 'User requested cancellation');
```

### Helper Functions

The package provides convenient helper functions for common operations:

```php
// Get the workflow engine instance
$engine = workflow();

// Start a workflow
$workflowId = start_workflow('my-workflow-id', $definition, $context);

// Get a workflow instance  
$instance = get_workflow('my-workflow-id');

// List all workflows
$workflows = list_workflows();

// List workflows filtered by state
$runningWorkflows = list_workflows(['state' => WorkflowState::RUNNING]);

// Cancel a workflow
cancel_workflow('my-workflow-id', 'User cancelled');
```

## 📖 Documentation

- **[Getting Started Guide](docs/getting-started.md)** - Complete setup and basic usage
- **[API Reference](docs/api-reference.md)** - Detailed API documentation  
- **[Advanced Features](docs/advanced-features.md)** - Error handling, timeouts, retries
- **[Best Practices](docs/best-practices.md)** - Performance tips and patterns
- **[Migration Guide](docs/migration.md)** - Upgrading from older versions
- **[Architecture](docs/development/ARCHITECTURE.md)** - Technical implementation details

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
