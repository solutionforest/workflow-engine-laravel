<?php

namespace SolutionForest\WorkflowMastery\Tests\Unit;

use SolutionForest\WorkflowMastery\Actions\DelayAction;
use SolutionForest\WorkflowMastery\Actions\LogAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;
use SolutionForest\WorkflowMastery\Tests\TestCase;

class ActionTest extends TestCase
{
    /** @test */
    public function log_action_can_execute(): void
    {
        $action = new LogAction(['message' => 'Hello {{name}}']);
        $context = new WorkflowContext('test-workflow', 'test-step', ['name' => 'John']);

        $result = $action->execute($context);

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getErrorMessage());
    }

    /** @test */
    public function delay_action_can_execute(): void
    {
        $action = new DelayAction(['seconds' => 1]);
        $context = new WorkflowContext('test-workflow', 'test-step');

        $start = microtime(true);
        $result = $action->execute($context);
        $end = microtime(true);

        $this->assertInstanceOf(ActionResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getErrorMessage());

        // Check that at least 1 second passed (with some tolerance)
        $this->assertGreaterThanOrEqual(0.9, $end - $start);
    }

    /** @test */
    public function log_action_handles_invalid_template(): void
    {
        $action = new LogAction(['message' => 'Hello {{invalid_variable}}']);
        $context = new WorkflowContext('test-workflow', 'test-step', ['name' => 'John']);

        $result = $action->execute($context);

        $this->assertTrue($result->isSuccess());
    }

    /** @test */
    public function delay_action_handles_invalid_seconds(): void
    {
        $action = new DelayAction(['seconds' => 'invalid']);
        $context = new WorkflowContext('test-workflow', 'test-step');

        $result = $action->execute($context);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Invalid delay seconds', $result->getErrorMessage());
    }
}
