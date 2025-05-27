<?php

namespace SolutionForest\WorkflowMastery\Core;

enum WorkflowState: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case WAITING = 'waiting';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function isActive(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::RUNNING,
            self::WAITING,
            self::PAUSED,
        ]);
    }

    public function isFinished(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
        ]);
    }
}
