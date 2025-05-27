<?php

namespace SolutionForest\WorkflowMastery\Facades;

use Illuminate\Support\Facades\Facade;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;

/**
 * @see \Solutionforest\LaravelWorkflowEngine\Core\WorkflowEngine
 */
class WorkflowMastery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WorkflowEngine::class;
    }
}
