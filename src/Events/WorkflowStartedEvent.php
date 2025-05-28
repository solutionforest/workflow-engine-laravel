<?php

namespace SolutionForest\WorkflowEngine\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

class WorkflowStartedEvent
{
    use Dispatchable, SerializesModels;

    public WorkflowInstance $instance;

    public function __construct(WorkflowInstance $instance)
    {
        $this->instance = $instance;
    }
}
