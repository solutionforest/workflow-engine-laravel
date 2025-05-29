# Getting Started Guide

## Installation

### Requirements

- PHP 8.3 or higher
- Laravel 10.0 or higher
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
php artisan migrate
```

## Basic Concepts

### Workflows

A workflow is a series of steps that process data. Think of it as a recipe that your application follows.

### Actions

Actions are the individual steps in your workflow. Each action performs a specific task.

### Context

Context holds the data that flows through your workflow. It's immutable and type-safe.

## Your First Workflow

Let's create a simple user registration workflow:

```php
<?php

use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use App\Actions\SendWelcomeEmailAction;
use App\Actions\CreateUserProfileAction;

// Create the workflow
$registrationWorkflow = WorkflowBuilder::create('user-registration')
    ->step('create-profile', CreateUserProfileAction::class)
    ->email('welcome-email', to: '{{ user.email }}', subject: 'Welcome!')
    ->delay(hours: 24)
    ->email('tips-email', to: '{{ user.email }}', subject: 'Getting Started Tips')
    ->build();

// Start the workflow
$instance = $registrationWorkflow->start([
    'user' => $user,
    'registration_data' => $registrationData
]);
```

## Creating Actions

Actions are simple PHP classes that implement the `WorkflowAction` interface:

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
}
```

See the [Advanced Features](advanced-features.md) guide for more details on attributes.

## Workflow States

Workflows have built-in states that you can monitor:

```php
use SolutionForest\WorkflowEngine\Core\WorkflowState;

$state = $instance->getState();

echo $state->value;       // 'running', 'completed', 'failed', etc.
echo $state->label();     // 'In Progress', 'Completed', 'Failed', etc.
echo $state->color();     // 'blue', 'green', 'red', etc.
echo $state->icon();      // '▶️', '✅', '❌', etc.
```

## Error Handling

The workflow engine automatically handles errors and provides retry mechanisms:

```php
$workflow = WorkflowBuilder::create('robust-workflow')
    ->step('risky-operation', RiskyAction::class)
        ->retry(attempts: 3, backoff: 'exponential')
        ->timeout(seconds: 30)
    ->build();
```

## Next Steps

- Learn about [Advanced Features](advanced-features.md)
- Read the [API Reference](api-reference.md)
- Check out [Best Practices](best-practices.md)
