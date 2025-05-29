<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions;

use SolutionForest\WorkflowEngine\Attributes\Retry;
use SolutionForest\WorkflowEngine\Attributes\Timeout;
use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

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
        $userData = $context->getData('user') ?? [];

        // Mock user profile creation
        $profile = [
            'id' => rand(1000, 9999),
            'user_id' => $userData['id'] ?? null,
            'name' => $userData['name'] ?? 'Unknown',
            'email' => $userData['email'] ?? 'unknown@example.com',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return ActionResult::success([
            'profile_id' => $profile['id'],
            'profile' => $profile,
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return true; // Always executable for testing
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
