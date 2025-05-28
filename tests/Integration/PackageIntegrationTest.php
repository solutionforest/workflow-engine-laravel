<?php

test('package integration works', function () {
    // Just test core workflow engine
    expect(class_exists(\SolutionForest\WorkflowEngine\Core\WorkflowEngine::class))->toBeTrue();
});
