<?php

use SolutionForest\WorkflowMastery\Actions\DelayAction;
use SolutionForest\WorkflowMastery\Actions\LogAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

test('log action can execute', function () {
    $action = new LogAction(['message' => 'Hello {{name}}']);
    $context = new WorkflowContext('test-workflow', 'test-step', ['name' => 'John']);

    $result = $action->execute($context);

    expect($result)->toBeInstanceOf(ActionResult::class);
    expect($result->isSuccess())->toBeTrue();
    expect($result->getErrorMessage())->toBeNull();
});

test('delay action can execute', function () {
    $action = new DelayAction(['seconds' => 1]);
    $context = new WorkflowContext('test-workflow', 'test-step');

    $start = microtime(true);
    $result = $action->execute($context);
    $end = microtime(true);

    expect($result)->toBeInstanceOf(ActionResult::class);
    expect($result->isSuccess())->toBeTrue();
    expect($result->getErrorMessage())->toBeNull();

    // Check that at least 1 second passed (with some tolerance)
    expect($end - $start)->toBeGreaterThanOrEqual(0.9);
});

test('log action handles invalid template', function () {
    $action = new LogAction(['message' => 'Hello {{invalid_variable}}']);
    $context = new WorkflowContext('test-workflow', 'test-step', ['name' => 'John']);

    $result = $action->execute($context);

    expect($result->isSuccess())->toBeTrue();
});

test('delay action handles invalid seconds', function () {
    $action = new DelayAction(['seconds' => 'invalid']);
    $context = new WorkflowContext('test-workflow', 'test-step');

    $result = $action->execute($context);

    expect($result->isSuccess())->toBeFalse();
    expect($result->getErrorMessage())->toContain('Invalid delay seconds');
});
