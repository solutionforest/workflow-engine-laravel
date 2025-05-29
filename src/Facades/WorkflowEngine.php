<?php

namespace SolutionForest\WorkflowEngine\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine as CoreWorkflowEngine;

/**
 * @see \SolutionForest\WorkflowEngine\Core\WorkflowEngine
 */
class WorkflowEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CoreWorkflowEngine::class;
    }
}
