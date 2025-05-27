<?php

namespace SolutionForest\WorkflowMastery\Commands;

use Illuminate\Console\Command;

class LaravelWorkflowEngineCommand extends Command
{
    public $signature = 'workflow-mastery';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
