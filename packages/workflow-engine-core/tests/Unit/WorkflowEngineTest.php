<?php

use Illuminate\Support\Facades\Event;
use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;
use SolutionForest\WorkflowMastery\Core\WorkflowState;
use SolutionForest\WorkflowMastery\Exceptions\InvalidWorkflowDefinitionException;
use SolutionForest\WorkflowMastery\Exceptions\WorkflowInstanceNotFoundException;

beforeEach(function () {
    $this->engine = app(WorkflowEngine::class);
});

test('it can start a workflow', function () {
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

    expect($workflowId)->not->toBeEmpty();

    // Verify the workflow instance was created
    $instance = $this->engine->getWorkflow($workflowId);
    expect($instance)->toBeInstanceOf(WorkflowInstance::class);
    expect($instance->getState())->toBe(WorkflowState::COMPLETED); // Log action completes immediately
    expect($instance->getName())->toBe('Test Workflow');
});

test('it can start a workflow with context', function () {
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
    expect($workflowData['name'])->toBe('John');
    expect($workflowData)->toHaveKey('logged_message'); // Added by LogAction
    expect($workflowData)->toHaveKey('logged_at'); // Added by LogAction
});

test('it can resume a paused workflow', function () {
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
    expect($instance->getState())->toBe(WorkflowState::COMPLETED);
});

test('it can cancel a workflow', function () {
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
    expect($instance->getState())->toBe(WorkflowState::CANCELLED);
});

test('it can get workflow status', function () {
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

    expect($status)->toBeArray();
    expect($status['state'])->toBe(WorkflowState::COMPLETED->value);
    expect($status['name'])->toBe('Test Workflow');
    expect($status)->toHaveKey('current_step');
    expect($status)->toHaveKey('progress');
});

test('it throws exception for invalid workflow definition', function () {
    $invalidDefinition = [
        'steps' => [],
    ];

    $this->engine->start('test-workflow', $invalidDefinition);
})->throws(InvalidWorkflowDefinitionException::class, 'Required field \'name\' is missing from workflow definition');

test('it throws exception for nonexistent workflow', function () {
    $this->engine->getWorkflow('nonexistent');
})->throws(WorkflowInstanceNotFoundException::class, 'Workflow instance \'nonexistent\' was not found');

test('it can list workflows', function () {
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

    expect($workflows)->toHaveCount(2);
    expect(array_column($workflows, 'workflow_id'))->toContain($workflowId1);
    expect(array_column($workflows, 'workflow_id'))->toContain($workflowId2);
});

test('it can filter workflows by state', function () {
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

    expect($completedWorkflows)->toHaveCount(1);
    expect($cancelledWorkflows)->toHaveCount(1);
    expect($completedWorkflows[0]['workflow_id'])->toBe($completedId);
    expect($cancelledWorkflows[0]['workflow_id'])->toBe($cancelledId);
});
