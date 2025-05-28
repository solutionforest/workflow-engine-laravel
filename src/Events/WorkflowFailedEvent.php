<?php

namespace SolutionForest\WorkflowEngine\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

class WorkflowFailedEvent
{
    use Dispatchable, SerializesModels;

    public WorkflowInstance $instance;

    public \Exception $exception;

    public function __construct(WorkflowInstance $instance, \Exception $exception)
    {
        $this->instance = $instance;
        $this->exception = $exception;
    }
}
