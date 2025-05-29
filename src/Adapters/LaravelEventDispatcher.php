<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Adapters;

use Illuminate\Contracts\Events\Dispatcher;
use SolutionForest\WorkflowEngine\Contracts\EventDispatcher;

/**
 * Laravel adapter for the workflow engine event dispatcher.
 * 
 * This adapter bridges Laravel's event system with the framework-agnostic
 * event dispatcher interface used by the workflow engine core.
 */
class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    public function dispatch(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
