<?php

namespace SolutionForest\WorkflowMastery\Core;

class Step
{
    public function __construct(
        private readonly string $id,
        private readonly ?string $actionClass = null,
        private readonly array $config = [],
        private readonly ?string $timeout = null,
        private readonly int $retryAttempts = 0,
        private readonly ?string $compensationAction = null,
        private readonly array $conditions = [],
        private readonly array $prerequisites = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getActionClass(): ?string
    {
        return $this->actionClass;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getTimeout(): ?string
    {
        return $this->timeout;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function getCompensationAction(): ?string
    {
        return $this->compensationAction;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getPrerequisites(): array
    {
        return $this->prerequisites;
    }

    public function hasAction(): bool
    {
        return $this->actionClass !== null;
    }

    public function hasCompensation(): bool
    {
        return $this->compensationAction !== null;
    }

    public function canExecute(array $data): bool
    {
        foreach ($this->conditions as $condition) {
            if (! $this->evaluateCondition($condition, $data)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        // Simple condition evaluation - can be enhanced later
        if (preg_match('/(\w+(?:\.\w+)*)\s*(===|==|!=|>=|<=|>|<)\s*(.+)/', $condition, $matches)) {
            $key = $matches[1];
            $operator = $matches[2];
            $value = trim($matches[3], '"\'');

            $dataValue = data_get($data, $key);

            return match ($operator) {
                '===' => $dataValue === $value,
                '==' => $dataValue == $value,
                '!=' => $dataValue != $value,
                '>' => $dataValue > $value,
                '<' => $dataValue < $value,
                '>=' => $dataValue >= $value,
                '<=' => $dataValue <= $value,
                default => false,
            };
        }

        return true; // Default to true if condition cannot be parsed
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->actionClass,
            'config' => $this->config,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retryAttempts,
            'compensation' => $this->compensationAction,
            'conditions' => $this->conditions,
            'prerequisites' => $this->prerequisites,
        ];
    }
}
