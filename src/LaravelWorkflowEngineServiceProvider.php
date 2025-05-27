<?php

namespace Solutionforest\LaravelWorkflowEngine;

use Solutionforest\LaravelWorkflowEngine\Commands\LaravelWorkflowEngineCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelWorkflowEngineServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('workflow-mastery')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_workflow_engine_table')
            ->hasCommand(LaravelWorkflowEngineCommand::class);
    }
}
