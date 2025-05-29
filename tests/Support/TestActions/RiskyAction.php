<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

class RiskyAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $config = $context->getConfig();
        $failureRate = $config['failure_rate'] ?? 0.3; // 30% chance of failure by default

        // Simulate risky operation that sometimes fails
        if (rand(1, 100) <= ($failureRate * 100)) {
            throw new \Exception('Simulated failure in risky operation');
        }

        return ActionResult::success([
            'operation_id' => 'risky_'.uniqid(),
            'success' => true,
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return true; // Always executable for testing
    }

    public function getName(): string
    {
        return 'Risky Operation';
    }

    public function getDescription(): string
    {
        return 'A simulated risky operation that sometimes fails for testing retry logic';
    }
}
