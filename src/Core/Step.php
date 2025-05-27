<?php

namespace SolutionForest\WorkflowMastery\Core;

class Step
{
    private string $id;

    private ?string $actionClass;

    private array $config;

    private ?string $timeout;

    private int $retryAttempts;

    private ?string $compensationAction;

    private array $conditions;

    private array $prerequisites;

    public function __construct(
        string $id,
        ?string $actionClass = null,
        array $config = [],
        ?string $timeout = null,
        int $retryAttempts = 0,
        ?string $compensationAction = null,
        array $conditions = [],
        array $prerequisites = []
    ) {
        $this->id = $id;
        $this->actionClass = $actionClass;
        $this->config = $config;
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
        $this->compensationAction = $compensationAction;
        $this->conditions = $conditions;
        $this->prerequisites = $prerequisites;
    }

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
        if (preg_match('/(\w+(?:\.\w+)*)\s*(===|==|!=|>|<|>=|<=)\s*(.+)/', $condition, $matches)) {
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
