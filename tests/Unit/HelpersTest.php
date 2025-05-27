<?php

namespace SolutionForest\WorkflowMastery\Tests\Unit;

use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Tests\TestCase;

class HelpersTest extends TestCase
{
    /** @test */
    public function workflow_helper_returns_engine_instance(): void
    {
        $engine = workflow();

        $this->assertInstanceOf(WorkflowEngine::class, $engine);
    }

    /** @test */
    public function start_workflow_helper_works(): void
    {
        $definition = [
            'name' => 'Helper Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello from helper'],
                ],
            ],
        ];

        $workflowId = start_workflow('helper-test', $definition);

        $this->assertNotEmpty($workflowId);
        $this->assertEquals('helper-test', $workflowId);
    }

    /** @test */
    public function get_workflow_helper_works(): void
    {
        $definition = [
            'name' => 'Helper Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello from helper'],
                ],
            ],
        ];

        $workflowId = start_workflow('helper-test-get', $definition);
        $instance = get_workflow($workflowId);

        $this->assertEquals($workflowId, $instance->getId());
        $this->assertEquals('Helper Test Workflow', $instance->getName());
    }

    /** @test */
    public function cancel_workflow_helper_works(): void
    {
        $definition = [
            'name' => 'Helper Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello from helper'],
                ],
            ],
        ];

        $workflowId = start_workflow('helper-test-cancel', $definition);
        cancel_workflow($workflowId, 'Test cancellation');

        $instance = get_workflow($workflowId);
        $this->assertEquals('cancelled', $instance->getState()->value);
    }
}
