<?php

namespace SolutionForest\WorkflowMastery\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SolutionForest\WorkflowMastery\LaravelWorkflowEngineServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'SolutionForest\\WorkflowMastery\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelWorkflowEngineServiceProvider::class,
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
