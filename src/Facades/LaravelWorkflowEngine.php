<?php

namespace Solutionforest\LaravelWorkflowEngine\Facades;

use Illuminate\Support\Facades\Facade;
use Solutionforest\LaravelWorkflowEngine\Core\WorkflowEngine;

/**
 * @see \Solutionforest\LaravelWorkflowEngine\Core\WorkflowEngine
 */
class LaravelWorkflowEngine extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WorkflowEngine::class;
    }
} Solutionforest\LaravelWorkflowEngine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Solutionforest\LaravelWorkflowEngine\LaravelWorkflowEngine
 */
class LaravelWorkflowEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Solutionforest\LaravelWorkflowEngine\LaravelWorkflowEngine::class;
    }
}
