# Workflow Engine Core

A framework-agnostic workflow engine for PHP applications. This is the core library that provides workflow definition, execution, and state management without any framework dependencies.

## Features

- **Framework Agnostic**: Works with any PHP framework or standalone
- **Type Safe**: Full PHP 8.1+ type safety with strict typing
- **Extensible**: Plugin architecture for custom actions and storage adapters
- **State Management**: Robust workflow instance state tracking
- **Error Handling**: Comprehensive exception handling with context
- **Performance**: Optimized for high-throughput workflow execution

## Installation

```bash
composer require solution-forest/workflow-engine-core
```

## Quick Start

```php
use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

// Define a workflow
$workflow = WorkflowBuilder::create('order-processing')
    ->addStep('validate', ValidateOrderAction::class)
    ->addStep('payment', ProcessPaymentAction::class)
    ->addStep('fulfillment', FulfillOrderAction::class)
    ->addTransition('validate', 'payment')
    ->addTransition('payment', 'fulfillment')
    ->build();

// Create execution context
$context = new WorkflowContext(
    workflowId: 'order-processing',
    stepId: 'validate',
    data: ['order_id' => 123, 'customer_id' => 456]
);

// Execute workflow
$engine = new WorkflowEngine();
$instance = $engine->start($workflow, $context);
$result = $engine->executeStep($instance, $context);
```

## Laravel Integration

For Laravel applications, use the Laravel integration package:

```bash
composer require solution-forest/workflow-engine-laravel
```

## Documentation

- [Getting Started](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Advanced Features](docs/advanced-features.md)

## License

MIT License. See [LICENSE](LICENSE) for details.
