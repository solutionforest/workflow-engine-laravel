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

    /**
     * Get color code for UI representation
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'blue',
            self::WAITING => 'yellow',
            self::PAUSED => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'purple',
        };
    }

    /**
     * Get icon for UI representation
     */
    public function icon(): string
    {
        return match ($this) {
            self::PENDING => '⏳',
            self::RUNNING => '▶️',
            self::WAITING => '⏸️',
            self::PAUSED => '⏸️',
            self::COMPLETED => '✅',
            self::FAILED => '❌',
            self::CANCELLED => '🚫',
        };
    }

    /**
     * Check if this state can transition to another state
     */
    public function canTransitionTo(self $state): bool
    {
        return match ($this) {
            self::PENDING => in_array($state, [self::RUNNING, self::CANCELLED]),
            self::RUNNING => in_array($state, [self::WAITING, self::PAUSED, self::COMPLETED, self::FAILED, self::CANCELLED]),
            self::WAITING => in_array($state, [self::RUNNING, self::FAILED, self::CANCELLED]),
            self::PAUSED => in_array($state, [self::RUNNING, self::CANCELLED]),
            default => false, // Terminal states cannot transition
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::WAITING => 'Waiting',
            self::PAUSED => 'Paused',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
