<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;

/** Starts a workflow on the queue: its first steps run in a worker, not in the web request. */
class StartWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $instanceId,
        public readonly array $definition,
        public readonly array $context = [],
    ) {
        $connection = config('workflow-engine.queue.connection');
        $queue = config('workflow-engine.queue.queue_name');
        $this->onConnection(is_string($connection) && $connection !== '' ? $connection : null);
        $this->onQueue(is_string($queue) && $queue !== '' ? $queue : null);
    }

    public function timeout(): int
    {
        $timeout = config('workflow-engine.queue.timeout', 300);

        return is_numeric($timeout) ? (int) $timeout : 300;
    }

    public function handle(WorkflowEngine $engine, StorageAdapter $storage): void
    {
        // A retried job must not start the same workflow twice.
        if ($storage->exists($this->instanceId)) {
            return;
        }
        $engine->start($this->instanceId, $this->definition, $this->context);
    }
}
