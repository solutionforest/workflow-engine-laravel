<?php

namespace Solutionforest\LaravelWorkflowEngine\Facades;

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
