<?php

use SolutionForest\WorkflowMastery\Core\WorkflowEngine;

test('workflow helper returns engine instance', function () {
    $engine = workflow();

    expect($engine)->toBeInstanceOf(WorkflowEngine::class);
});

test('start workflow helper works', function () {
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

    expect($workflowId)->not->toBeEmpty();
    expect($workflowId)->toBe('helper-test');
});

test('get workflow helper works', function () {
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

    expect($instance->getId())->toBe($workflowId);
    expect($instance->getName())->toBe('Helper Test Workflow');
});

test('cancel workflow helper works', function () {
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
    expect($instance->getState()->value)->toBe('cancelled');
});
