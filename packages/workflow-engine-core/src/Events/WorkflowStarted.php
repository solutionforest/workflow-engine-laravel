<?php

namespace SolutionForest\WorkflowEngine\Events;

class WorkflowStarted
{
    public function __construct(
        public readonly string $workflowId,
        public readonly string $name,
        public readonly array $context = []
    ) {}
}
