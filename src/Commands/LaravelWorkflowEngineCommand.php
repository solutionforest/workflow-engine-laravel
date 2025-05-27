<?php

namespace Solutionforest\LaravelWorkflowEngine\Commands;

use Illuminate\Console\Command;

class LaravelWorkflowEngineCommand extends Command
{
    public $signature = 'laravel-workflow-engine';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
