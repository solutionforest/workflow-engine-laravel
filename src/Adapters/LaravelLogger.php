<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Adapters;

use Illuminate\Log\LogManager;
use SolutionForest\WorkflowEngine\Contracts\Logger;

/**
 * Laravel adapter for the workflow engine logger.
 *
 * This adapter bridges Laravel's logging system with the framework-agnostic
 * logger interface used by the workflow engine core.
 */
class LaravelLogger implements Logger
{
    public function __construct(
        private readonly LogManager $logger
    ) {}

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }
}
