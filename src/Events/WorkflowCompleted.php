<?php

namespace SolutionForest\WorkflowEngine\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $workflowId,
        public readonly string $name,
        public readonly array $result = []
    ) {}
}
