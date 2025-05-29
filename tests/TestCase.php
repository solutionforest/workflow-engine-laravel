<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SolutionForest\WorkflowEngine\Laravel\Providers\WorkflowEngineServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SolutionForest\\WorkflowEngine\\Laravel\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            WorkflowEngineServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('definition_name');
            $table->string('definition_version');
            $table->json('definition_data');
            $table->string('state');
            $table->json('data');
            $table->string('current_step_id')->nullable();
            $table->json('completed_steps');
            $table->json('failed_steps');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('state');
            $table->index('definition_name');
        });
    }
}
