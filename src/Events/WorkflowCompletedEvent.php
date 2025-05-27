<?php

namespace SolutionForest\WorkflowMastery\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;

class WorkflowCompletedEvent
{
    use Dispatchable, SerializesModels;

    public WorkflowInstance $instance;

    public function __construct(WorkflowInstance $instance)
    {
        $this->instance = $instance;
    }
}
