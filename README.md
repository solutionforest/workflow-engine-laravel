# Laravel Workflow Engine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-engine-laravel.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-engine-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solutionforest/workflow-engine-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solutionforest/workflow-engine-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-engine-laravel.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-engine-laravel)

**A modern, type-safe workflow engine for Laravel built with PHP 8.3+ features**

Create powerful business workflows with a beautiful, fluent API. Turn complex processes into simple, maintainable code.

## ✨ Why Choose This Workflow Engine?

- 🎨 **Beautiful Fluent API** - Write workflows that read like documentation
- 🔒 **Type Safety First** - Built with readonly properties, enums, and strong typing
- ⚡ **Lightning Fast** - Modern PHP 8.3+ features for optimal performance
- 🧩 **Laravel Native** - Seamless integration with Laravel's ecosystem
- 📚 **Easy to Learn** - 70% less code than traditional workflow engines

---

## 🚀 Quick Start

### Installation

```bash
composer require solution-forest/workflow-engine-laravel
php artisan vendor:publish --tag="workflow-engine-config"
php artisan migrate
```

### Your First Workflow in 30 Seconds

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Support\SimpleWorkflow;

// Create a complete user onboarding workflow in one line
$workflow = SimpleWorkflow::quick()
    ->email('welcome@company.com', 'Welcome!')
    ->delay(days: 1)
    ->email('tips@company.com', 'Getting Started Tips')
    ->build();

// Or build custom workflows with our fluent API
$workflow = WorkflowBuilder::create('order-processing')
    ->email('order-confirmation', to: '{{ customer.email }}')
    ->http('POST', 'https://api.payment.com/charge', ['amount' => '{{ order.total }}'])
    ->delay(minutes: 5)
    ->when('payment.successful', fn($builder) => 
        $builder->email('shipping-notification', to: '{{ customer.email }}')
    )
    ->build();

// Start the workflow
$instance = $workflow->start(['customer' => $customer, 'order' => $order]);
```

## 💼 Real-World Examples

### E-commerce Order Processing

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;

$workflow = WorkflowBuilder::create('order-processing')
    ->step('validate-order', ValidateOrderAction::class)
    ->step('check-inventory', CheckInventoryAction::class)
    ->step('process-payment', ProcessPaymentAction::class)
    ->step('create-shipment', CreateShipmentAction::class)
    ->email('order-complete', to: '{{ customer.email }}')
    ->build();
```

### Document Approval Process

```php
$workflow = WorkflowBuilder::create('document-approval')
    ->step('submit', SubmitDocumentAction::class)
    ->step('manager-review', ManagerReviewAction::class)
        ->timeout(days: 2)
    ->step('final-approval', FinalApprovalAction::class)
    ->email('approval-complete', to: '{{ submitter.email }}')
    ->build();
```

### User Onboarding

```php
$workflow = SimpleWorkflow::sequential([
    'send-welcome-email',
    'schedule-followup',
    'assign-to-team',
    'complete-onboarding'
])->build();
```

## 🔧 Core Features

### Modern PHP 8.3+ Enums

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

// Rich, type-safe workflow states
$state = WorkflowState::Running;
echo $state->color();     // 'blue'
echo $state->icon();      // '▶️'
echo $state->label();     // 'In Progress'

// Smart state transitions
if ($state->canTransitionTo(WorkflowState::Completed)) {
    $workflow->complete();
}
```

### Attribute-Based Configuration

```php
use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Attributes\Timeout;
use SolutionForest\WorkflowEngine\Attributes\Retry;

class ProcessPaymentAction implements WorkflowAction
{
    #[WorkflowStep('process-payment')]
    #[Timeout(minutes: 5)]
    #[Retry(attempts: 3, backoff: 'exponential')]
    public function execute(WorkflowContext $context): ActionResult
    {
        // Your payment logic here
        return ActionResult::success();
    }
}
```

### Smart HTTP Actions

```php
$workflow = WorkflowBuilder::create('api-integration')
    ->http('POST', 'https://api.example.com/users', [
        'name' => '{{ user.name }}',
        'email' => '{{ user.email }}',
        'role' => '{{ user.role }}'
    ])
    ->retry(attempts: 3)
    ->timeout(seconds: 30)
    ->build();
```

### Conditional Logic

```php
$workflow = WorkflowBuilder::create('conditional-flow')
    ->when('user.type == "premium"', fn($builder) =>
        $builder->email('premium-welcome', to: '{{ user.email }}')
    )
    ->when('user.age >= 18', fn($builder) =>
        $builder->step('verify-identity', VerifyIdentityAction::class)
    )
    ->build();
```

## 📖 Documentation

- **[Getting Started Guide](docs/getting-started.md)** - Complete setup and basic usage
- **[API Reference](docs/api-reference.md)** - Detailed API documentation
- **[Advanced Features](docs/advanced-features.md)** - Error handling, timeouts, retries
- **[Best Practices](docs/best-practices.md)** - Performance tips and patterns
- **[Migration Guide](docs/migration.md)** - Upgrading from older versions

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
