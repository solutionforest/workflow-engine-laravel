
# Workflow Mastery: A Universal Workflow Engine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/solution-forest/workflow-mastery/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/solution-forest/workflow-mastery/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/workflow-mastery.svg?style=flat-square)](https://packagist.org/packages/solution-forest/workflow-mastery)

---

## What is a Workflow Engine?

A **workflow engine** is a software component that manages and executes defined sequences of tasks, called workflows. It allows you to model business processes, automate repetitive tasks, and coordinate actions across systems or modules. By decoupling process logic from application code, workflow engines make your systems more flexible, maintainable, and adaptable to change.

### Real-World Examples

- **Order Processing**: E-commerce platforms use workflows to handle order validation, payment, inventory checks, shipping, and notifications.
- **Document Approval**: HR or legal departments automate document review, approval, and archiving.
- **CI/CD Pipelines**: DevOps tools like GitHub Actions, Jenkins, or GitLab CI define build, test, and deploy workflows.

### Workflow Engines in Other Languages

- **Java**: [Camunda](https://camunda.com/), [Activiti](https://www.activiti.org/)
- **C#/.NET**: [Elsa Workflows](https://elsa-workflows.github.io/elsa-core/)
- **Ruby**: [Ruote](https://github.com/jmettraux/ruote)
- **Go**: [Conductor](https://github.com/netflix/conductor), [Temporal](https://temporal.io/)
- **Rust**: [rusty-workflow](https://github.com/whatisinternet/rusty-workflow)

---

## Why "Workflow Mastery"?

 Inspired by the best ideas from many languages and platforms, **Workflow Mastery** aims to be a universal, modular, and extensible workflow engine. While it integrates seamlessly with Laravel, its core logic is designed to be framework-agnostic, so you can use it anywhere.

---

## Design Philosophy & Best Practices

- **Separation of Concerns**: Keep workflow definitions and execution logic outside of business modules.
- **Extensibility**: Support custom actions, conditions, and event hooks.
- **Portability**: Core logic should not depend on Laravel or any specific framework.
- **Declarative DSL**: Define workflows in a simple, human-readable format (YAML, JSON, PHP array, etc).
- **Persistence Agnostic**: Allow pluggable storage (DB, file, memory, etc).
- **Observability**: Provide hooks for logging, monitoring, and debugging.

---

## Example: Laravel Workflow Definition (Simple DSL)

```php
$workflow = [
    'name' => 'Order Processing',
    'steps' => [
        ['name' => 'Validate Order', 'action' => 'App\\Actions\\ValidateOrder'],
        ['name' => 'Charge Payment', 'action' => 'App\\Actions\\ChargePayment'],
        ['name' => 'Update Inventory', 'action' => 'App\\Actions\\UpdateInventory'],
        ['name' => 'Send Confirmation', 'action' => 'App\\Actions\\SendConfirmationEmail'],
    ],
    'transitions' => [
        ['from' => 'Validate Order', 'to' => 'Charge Payment', 'condition' => 'orderIsValid'],
        ['from' => 'Charge Payment', 'to' => 'Update Inventory'],
        ['from' => 'Update Inventory', 'to' => 'Send Confirmation'],
    ],
];
```

---

## Installation

You can install the package via composer:

```bash
composer require solution-forest/workflow-mastery
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="workflow-mastery-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="workflow-mastery-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="workflow-mastery-views"
```

---

## Usage

```php
$engine = new SolutionForest\WorkflowMastery();
$engine->run($workflow, $data);
```

---

## Suggestions for Flexibility

- Use interfaces for actions and conditions.
- Allow dynamic workflow loading (from DB, files, or code).
- Support async and parallel steps.
- Provide adapters for different frameworks.
- Make the engine observable (events, logs, metrics).

---

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [alan](https://github.com/)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[![Latest Version on Packagist](https://img.shields.io/packagist/v//workflow-mastery.svg?style=flat-square)](https://packagist.org/packages//workflow-mastery)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status//workflow-mastery/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com//workflow-mastery/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status//workflow-mastery/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com//workflow-mastery/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt//workflow-mastery.svg?style=flat-square)](https://packagist.org/packages//workflow-mastery)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/workflow-mastery.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/workflow-mastery)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require /workflow-mastery
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="workflow-mastery-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="workflow-mastery-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="workflow-mastery-views"
```

## Usage

```php
$laravelWorkflowEngine = new Solutionforest\LaravelWorkflowEngine();
echo $laravelWorkflowEngine->echoPhrase('Hello, Solutionforest!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [alan](https://github.com/)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
