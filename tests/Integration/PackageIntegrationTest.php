<?php

use SolutionForest\WorkflowEngine\Core\WorkflowEngine;

test('package integration works', function () {
    // Just test core workflow engine
    expect(class_exists(WorkflowEngine::class))->toBeTrue();
});
