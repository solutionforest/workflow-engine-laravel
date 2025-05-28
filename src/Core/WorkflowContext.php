<?php

namespace SolutionForest\WorkflowMastery\Core;

use DateTime;

/**
 * Immutable workflow context using PHP 8.3+ readonly properties
 */
readonly class WorkflowContext
{
    public function __construct(
        public string $workflowId,
        public string $stepId,
        public array $data = [],
        public array $config = [],
        public ?WorkflowInstance $instance = null,
        public DateTime $executedAt = new DateTime()
    ) {}

    /**
     * Get workflow ID
     */
    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    /**
     * Get step ID
     */
    public function getStepId(): string
    {
        return $this->stepId;
    }

    /**
     * Get all data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get all data (alias for compatibility)
     */
    public function getAllData(): array
    {
        return $this->data;
    }

    /**
     * Create new context with additional data (immutable)
     */
    public function withData(array $newData): self
    {
        return new self(
            workflowId: $this->workflowId,
            stepId: $this->stepId,
            data: array_merge($this->data, $newData),
            config: $this->config,
            instance: $this->instance,
            executedAt: $this->executedAt
        );
    }

    /**
     * Create new context with a single data value (immutable)
     */
    public function with(string $key, mixed $value): self
    {
        $newData = $this->data;
        data_set($newData, $key, $value);
        
        return new self(
            workflowId: $this->workflowId,
            stepId: $this->stepId,
            data: $newData,
            config: $this->config,
            instance: $this->instance,
            executedAt: $this->executedAt
        );
    }

    /**
     * Check if data key exists
     */
    public function hasData(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    /**
     * Get configuration value
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    /**
     * Get current step ID (alias for compatibility)
     */
    public function getCurrentStepId(): string
    {
        return $this->stepId;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'workflow_id' => $this->workflowId,
            'step_id' => $this->stepId,
            'data' => $this->data,
            'config' => $this->config,
            'instance_id' => $this->instance?->getId(),
            'executed_at' => $this->executedAt->format('Y-m-d H:i:s'),
        ];
    }
}
