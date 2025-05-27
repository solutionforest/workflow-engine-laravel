<?php

namespace SolutionForest\WorkflowMastery\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SolutionForest\WorkflowMastery\Core\Step;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;

class StepCompletedEvent
{
    use Dispatchable, SerializesModels;

    public WorkflowInstance $instance;

    public Step $step;

    public function __construct(WorkflowInstance $instance, Step $step)
    {
        $this->instance = $instance;
        $this->step = $step;
    }
}
