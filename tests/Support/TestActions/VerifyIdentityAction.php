<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

class VerifyIdentityAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $userData = $context->getData('user') ?? [];

        // Mock identity verification
        $verificationResult = [
            'verification_id' => 'verify_'.uniqid(),
            'user_id' => $userData['id'] ?? null,
            'status' => 'verified',
            'verified_at' => date('Y-m-d H:i:s'),
        ];

        return ActionResult::success([
            'verification' => $verificationResult,
            'identity_verified' => true,
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        $userData = $context->getData('user') ?? [];

        return isset($userData['id']) && ($userData['age'] ?? 0) >= 18;
    }

    public function getName(): string
    {
        return 'Verify Identity';
    }

    public function getDescription(): string
    {
        return 'Verifies user identity for users 18 years and older';
    }
}
