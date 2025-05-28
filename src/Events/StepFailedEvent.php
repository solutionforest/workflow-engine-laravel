<?php

namespace SolutionForest\WorkflowEngine\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SolutionForest\WorkflowEngine\Core\Step;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

class StepFailedEvent
{
    use Dispatchable, SerializesModels;

    public WorkflowInstance $instance;

    public Step $step;

    public \Exception $exception;

    public function __construct(WorkflowInstance $instance, Step $step, \Exception $exception)
    {
        $this->instance = $instance;
        $this->step = $step;
        $this->exception = $exception;
    }
}
