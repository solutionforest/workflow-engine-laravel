<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Exceptions\ConcurrentModificationException;

/**
 * Delivers a signal on the queue, so the steps after the waiting one run in a worker, not in
 * the web request. A conflict (someone else advanced the instance) is retried with back-off.
 */
class SignalWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly string $instanceId,
        public readonly string $signal,
        public readonly array $payload = [],
    ) {
        $this->onConnection(self::setting('workflow-engine.queue.connection'));
        $this->onQueue(self::setting('workflow-engine.queue.queue_name'));
    }

    /** Seconds a worker may spend on the steps this signal runs. */
    public function timeout(): int
    {
        $timeout = config('workflow-engine.queue.timeout', 300);

        return is_numeric($timeout) ? (int) $timeout : 300;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [1, 5, 15, 60];
    }

    public function handle(WorkflowEngine $engine): void
    {
        try {
            $engine->signal($this->instanceId, $this->signal, $this->payload);
        } catch (ConcurrentModificationException) {
            $this->release($this->backoff()[min($this->attempts() - 1, 3)] ?? 60);
        }
    }

    private static function setting(string $key): ?string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
