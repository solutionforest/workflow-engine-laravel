<?php

namespace SolutionForest\WorkflowEngine\Laravel\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use SolutionForest\WorkflowEngine\Contracts\EventDispatcher;
use SolutionForest\WorkflowEngine\Contracts\Logger;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Laravel\Adapters\LaravelEventDispatcher;
use SolutionForest\WorkflowEngine\Laravel\Adapters\LaravelLogger;
use SolutionForest\WorkflowEngine\Laravel\Commands\ResumeDueWorkflowsCommand;
use SolutionForest\WorkflowEngine\Laravel\Storage\DatabaseStorage;

class WorkflowEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/workflow-engine.php',
            'workflow-engine'
        );

        // Register storage adapter
        $this->app->singleton(StorageAdapter::class, function ($app): StorageAdapter {
            $driver = config('workflow-engine.storage.driver', 'database');

            return match ($driver) {
                'database' => new DatabaseStorage(
                    $app->make(DatabaseManager::class),
                    self::string('workflow-engine.storage.database.table') ?? 'workflow_instances',
                    self::string('workflow-engine.storage.database.connection'),
                ),
                default => throw new \InvalidArgumentException('Unsupported storage driver: '.(is_string($driver) ? $driver : get_debug_type($driver)))
            };
        });

        // Register event dispatcher adapter
        $this->app->singleton(EventDispatcher::class, function ($app) {
            return new LaravelEventDispatcher(
                $app->make(Dispatcher::class)
            );
        });

        // Register logger adapter
        $this->app->singleton(Logger::class, function ($app) {
            return new LaravelLogger(
                $app->make(LogManager::class)
            );
        });

        // Register workflow engine
        $this->app->singleton(WorkflowEngine::class, function ($app): WorkflowEngine {
            return new WorkflowEngine(
                $app->make(StorageAdapter::class),
                $app->make(EventDispatcher::class),
                $app->make(Logger::class),
                // SIGALRM timeouts only in a CLI worker that runs one workflow at a time.
                config('workflow-engine.actions.signal_timeouts') === true,
            );
        });

        // Register alias
        $this->app->alias(WorkflowEngine::class, 'workflow.mastery');
        $this->app->alias(WorkflowEngine::class, 'workflow.engine');
    }

    public function boot(): void
    {
        // Publish config file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/workflow-engine.php' => config_path('workflow-engine.php'),
            ], 'workflow-engine-config');
        }

        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../database/migrations/create_workflow_instances_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_workflow_instances_table.php'),
            ], 'workflow-engine-migrations');
            $this->publishes([
                __DIR__.'/../../database/migrations/upgrade_workflow_instances_table_to_v2.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_upgrade_workflow_instances_table_to_v2.php'),
            ], 'workflow-engine-upgrade-migrations');
        }

        // Publish views
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/workflow-engine'),
            ], 'workflow-engine-views');
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ResumeDueWorkflowsCommand::class,
            ]);
        }

        if (config('workflow-engine.schedule.resume_due', true) !== false) {
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('workflow:resume-due')->everyMinute()->withoutOverlapping()->onOneServer();
            });
        }
    }

    private static function string(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
