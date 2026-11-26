<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Commands;

use Illuminate\Console\Command;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;

/**
 * Resumes suspended workflows whose wait time has passed (delays, deadlines, reminders). The
 * service provider schedules it every minute unless `workflow-engine.schedule.resume_due` is
 * false; it is safe to run on several servers at once (saves are revision-checked).
 */
class ResumeDueWorkflowsCommand extends Command
{
    public $signature = 'workflow:resume-due {--limit=100 : Most instances to resume in one run}';

    public $description = 'Resume suspended workflows whose wait time has passed';

    public function handle(WorkflowEngine $engine): int
    {
        $limit = $this->option('limit');
        $resumed = $engine->resumeDue(limit: is_numeric($limit) ? max(1, (int) $limit) : 100);
        $this->components->info(count($resumed).' workflow(s) resumed.');

        return self::SUCCESS;
    }
}
