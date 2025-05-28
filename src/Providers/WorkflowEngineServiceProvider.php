<?php

namespace SolutionForest\WorkflowEngine\Laravel\Providers;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\DatabaseManager;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Laravel\Commands\LaravelWorkflowEngineCommand;
use SolutionForest\WorkflowEngine\Laravel\Storage\DatabaseStorage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WorkflowEngineServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('workflow-engine')
            ->hasConfigFile('workflow-engine')
            ->hasViews()
            ->hasMigration('create_workflow_instances_table')
            ->hasCommand(LaravelWorkflowEngineCommand::class);
    }

    public function register(): void
    {
        parent::register();

        // Register storage adapter
        $this->app->singleton(StorageAdapter::class, function ($app): StorageAdapter {
            $driver = config('workflow-engine.storage.driver', 'database');

            return match ($driver) {
                'database' => new DatabaseStorage(
                    $app->make(DatabaseManager::class),
                    config('workflow-engine.storage.database.table', 'workflow_instances')
                ),
                default => throw new \InvalidArgumentException("Unsupported storage driver: {$driver}")
            };
        });

        // Register workflow engine
        $this->app->singleton(WorkflowEngine::class, function ($app): WorkflowEngine {
            return new WorkflowEngine(
                $app->make(StorageAdapter::class),
                $app->make(EventDispatcher::class)
            );
        });

        // Register alias
        $this->app->alias(WorkflowEngine::class, 'workflow.mastery');
        $this->app->alias(WorkflowEngine::class, 'workflow.engine');
    }

    public function boot(): void
    {
        parent::boot();
    }
}
