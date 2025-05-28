<?php

namespace SolutionForest\WorkflowMastery\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SolutionForest\WorkflowMastery\LaravelWorkflowEngine
 */
class LaravelWorkflowEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SolutionForest\WorkflowMastery\LaravelWorkflowEngine::class;
    }
}
