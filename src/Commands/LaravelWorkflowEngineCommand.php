<?php

namespace SolutionForest\WorkflowEngine\Laravel\Commands;

use Illuminate\Console\Command;

class LaravelWorkflowEngineCommand extends Command
{
    public $signature = 'workflow-engine';

    public $description = 'Laravel Workflow Engine command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
