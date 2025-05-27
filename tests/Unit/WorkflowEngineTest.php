<?php

namespace SolutionForest\WorkflowMastery\Tests\Unit;

use Illuminate\Support\Facades\Event;
use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;
use SolutionForest\WorkflowMastery\Core\WorkflowState;
use SolutionForest\WorkflowMastery\Events\WorkflowCancelled;
use SolutionForest\WorkflowMastery\Events\WorkflowStarted;
use SolutionForest\WorkflowMastery\Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    private WorkflowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(WorkflowEngine::class);
    }

    /** @test */
    public function it_can_start_a_workflow(): void
    {
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
            ],
        ];

        $workflowId = $this->engine->start('test-workflow', $definition);

        $this->assertNotEmpty($workflowId);

        // Verify the workflow instance was created
        $instance = $this->engine->getWorkflow($workflowId);
        $this->assertInstanceOf(WorkflowInstance::class, $instance);
        $this->assertEquals(WorkflowState::COMPLETED, $instance->getState()); // Log action completes immediately
        $this->assertEquals('Test Workflow', $instance->getName());
    }

    /** @test */
    public function it_can_start_a_workflow_with_context(): void
    {
        Event::fake();

        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello {{name}}'],
                ],
            ],
        ];

        $context = ['name' => 'John'];
        $workflowId = $this->engine->start('test-workflow', $definition, $context);

        $instance = $this->engine->getWorkflow($workflowId);
        $workflowData = $instance->getContext()->getData();
        
        // Should contain original context plus any data added by actions
        $this->assertEquals('John', $workflowData['name']);
        $this->assertArrayHasKey('logged_message', $workflowData); // Added by LogAction
        $this->assertArrayHasKey('logged_at', $workflowData); // Added by LogAction
    }

    /** @test */
    public function it_can_resume_a_paused_workflow(): void
    {
        Event::fake();

        // Create a workflow with multiple steps
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
                [
                    'id' => 'step2',
                    'name' => 'Second Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Second step'],
                ],
            ],
        ];

        $workflowId = $this->engine->start('test-workflow', $definition);

        // Manually pause it
        $storage = app(StorageAdapter::class);
        $instance = $storage->load($workflowId);
        $instance->setState(WorkflowState::PAUSED);
        $storage->save($instance);

        // Resume it
        $this->engine->resume($workflowId);

        $instance = $this->engine->getWorkflow($workflowId);
        // After resume, it should be completed since we have simple log actions
        $this->assertEquals(WorkflowState::COMPLETED, $instance->getState());
    }

    /** @test */
    public function it_can_cancel_a_workflow(): void
    {
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
            ],
        ];

        $workflowId = $this->engine->start('test-workflow', $definition);
        $this->engine->cancel($workflowId, 'User cancelled');

        $instance = $this->engine->getWorkflow($workflowId);
        $this->assertEquals(WorkflowState::CANCELLED, $instance->getState());
    }

    /** @test */
    public function it_can_get_workflow_status(): void
    {
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
            ],
        ];

        $workflowId = $this->engine->start('test-workflow', $definition);
        $status = $this->engine->getStatus($workflowId);

        $this->assertIsArray($status);
        $this->assertEquals(WorkflowState::COMPLETED->value, $status['state']);
        $this->assertEquals('Test Workflow', $status['name']);
        $this->assertArrayHasKey('current_step', $status);
        $this->assertArrayHasKey('progress', $status);
    }

    /** @test */
    public function it_throws_exception_for_invalid_workflow_definition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow definition must have a name');

        $invalidDefinition = [
            'steps' => [],
        ];

        $this->engine->start('test-workflow', $invalidDefinition);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_workflow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow not found: nonexistent');

        $this->engine->getWorkflow('nonexistent');
    }

    /** @test */
    public function it_can_list_workflows(): void
    {
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
            ],
        ];

        $workflowId1 = $this->engine->start('test-workflow-1', $definition);
        $workflowId2 = $this->engine->start('test-workflow-2', $definition);

        $workflows = $this->engine->listWorkflows();

        $this->assertCount(2, $workflows);
        $this->assertContains($workflowId1, array_column($workflows, 'workflow_id'));
        $this->assertContains($workflowId2, array_column($workflows, 'workflow_id'));
    }

    /** @test */
    public function it_can_filter_workflows_by_state(): void
    {
        $definition = [
            'name' => 'Test Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Hello World'],
                ],
            ],
        ];

        $completedId = $this->engine->start('completed-workflow', $definition);
        $cancelledId = $this->engine->start('cancelled-workflow', $definition);

        $this->engine->cancel($cancelledId);

        $completedWorkflows = $this->engine->listWorkflows(['state' => WorkflowState::COMPLETED]);
        $cancelledWorkflows = $this->engine->listWorkflows(['state' => WorkflowState::CANCELLED]);

        $this->assertCount(1, $completedWorkflows);
        $this->assertCount(1, $cancelledWorkflows);
        $this->assertEquals($completedId, $completedWorkflows[0]['workflow_id']);
        $this->assertEquals($cancelledId, $cancelledWorkflows[0]['workflow_id']);
    }
}
