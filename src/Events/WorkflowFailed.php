<?php

namespace SolutionForest\WorkflowMastery\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $workflowId,
        public readonly string $name,
        public readonly string $error,
        public readonly array $context = []
    ) {}
}
