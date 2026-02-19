# Getting Started Guide

## Installation

### Requirements

- PHP 8.3 or higher
- Laravel 11.0 or higher
- Composer

### Install the Package

```bash
composer require solution-forest/workflow-engine-laravel
```

### Publish Configuration

```bash
php artisan vendor:publish --tag="workflow-engine-config"
```

### Run Migrations

```bash
php artisan vendor:publish --tag="workflow-engine-migrations"
php artisan migrate
```

## Basic Concepts

### Workflows

A workflow is a series of steps that process data. Think of it as a recipe that your application follows.

### Actions

Actions are the individual steps in your workflow. Each action performs a specific task and implements the `WorkflowAction` interface.

### Context

Context holds the data that flows through your workflow. It's immutable and type-safe via the `WorkflowContext` readonly class.

## Your First Workflow

Let's create a simple user registration workflow:

```php
<?php

use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use App\Actions\CreateUserProfileAction;

// Create the workflow definition
$definition = WorkflowBuilder::create('user-registration')
    ->description('New user registration process')
    ->addStep('create-profile', CreateUserProfileAction::class)
    ->email('welcome-email', '{{ user.email }}', 'Welcome!')
    ->delay(hours: 24)
    ->email('tips-email', '{{ user.email }}', 'Getting Started Tips')
    ->build();

// Start the workflow via the engine
$engine = app(WorkflowEngine::class);
$instanceId = $engine->start('user-reg-001', $definition->toArray(), [
    'user' => ['id' => 1, 'email' => 'user@example.com', 'name' => 'John'],
]);
```

## Creating Actions

Actions are PHP classes that implement the `WorkflowAction` interface, which requires four methods:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;
use SolutionForest\WorkflowEngine\Core\ActionResult;

class CreateUserProfileAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $userData = $context->getData('user');

        // Create user profile logic here
        $profile = UserProfile::create([
            'user_id' => $userData['id'],
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        return ActionResult::success([
            'profile_id' => $profile->id
        ]);
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

### Enhanced Actions with Attributes

Use PHP 8.3+ attributes to add configuration directly to your action classes:

```php
<?php

namespace App\Actions;

use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Attributes\Timeout;
use SolutionForest\WorkflowEngine\Attributes\Retry;
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;
use SolutionForest\WorkflowEngine\Core\ActionResult;

#[WorkflowStep(
    id: 'create_profile',
    name: 'Create User Profile',
    description: 'Creates a new user profile in the database'
)]
#[Timeout(seconds: 30)]
#[Retry(attempts: 3, backoff: 'exponential')]
class CreateUserProfileAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        // Same implementation as above
        // Now with automatic timeout and retry handling
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

See the [Advanced Features](advanced-features.md) guide for more details on attributes.

## Workflow States

Workflows have built-in states that you can monitor:

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

$state = $instance->getState();

echo $state->value;         // 'running', 'completed', 'failed', etc.
echo $state->label();       // 'Running', 'Completed', 'Failed', etc.
echo $state->color();       // 'blue', 'green', 'red', etc.
echo $state->icon();        // '▶️', '✅', '❌', etc.
echo $state->description(); // Detailed description of the state

// Check state categories
$state->isActive();     // true for PENDING, RUNNING, WAITING, PAUSED
$state->isFinished();   // true for COMPLETED, FAILED, CANCELLED
$state->isSuccessful(); // true only for COMPLETED
$state->isError();      // true only for FAILED

// Valid state transitions
$state->canTransitionTo(WorkflowState::COMPLETED); // Check transition validity
$state->getValidTransitions(); // Get all valid target states
```

## Error Handling

The workflow engine provides comprehensive error handling:

```php
$workflow = WorkflowBuilder::create('robust-workflow')
    ->addStep('risky-operation', RiskyAction::class, [], 30, 3) // timeout: 30s, retry: 3 attempts
    ->addStep('slow-task', SlowAction::class, timeout: '5m')     // timeout as string format
    ->build();
```

## Using the Facade and Helpers

```php
use SolutionForest\WorkflowEngine\Laravel\Facades\WorkflowEngine;

// Via facade
$instanceId = WorkflowEngine::start('my-workflow', $definition, $context);
$instance = WorkflowEngine::getInstance($instanceId);
$instance = WorkflowEngine::cancel($instanceId, 'No longer needed');

// Via helper function
$engine = workflow();
$instanceId = $engine->start('my-workflow', $definition, $context);
```

## Next Steps

- Learn about [Advanced Features](advanced-features.md)
- Read the [API Reference](api-reference.md)
- Check out [Best Practices](best-practices.md)
