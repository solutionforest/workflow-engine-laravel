<?php

namespace SolutionForest\WorkflowMastery\Core;

class WorkflowContext
{
    private string $workflowId;

    private string $stepId;

    private array $data;

    private array $config;

    public function __construct(
        string $workflowId,
        string $stepId,
        array $data = [],
        array $config = []
    ) {
        $this->workflowId = $workflowId;
        $this->stepId = $stepId;
        $this->data = $data;
        $this->config = $config;
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    public function getStepId(): string
    {
        return $this->stepId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getAllData(): array
    {
        return $this->data;
    }

    public function setData(string $key, $value): void
    {
        data_set($this->data, $key, $value);
    }

    public function hasData(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    public function getCurrentStepId(): string
    {
        return $this->stepId;
    }

    public function toArray(): array
    {
        return [
            'workflow_id' => $this->workflowId,
            'step_id' => $this->stepId,
            'data' => $this->data,
            'config' => $this->config,
        ];
    }
}
