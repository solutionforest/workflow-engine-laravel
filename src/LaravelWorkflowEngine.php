<?php

namespace SolutionForest\WorkflowMastery;

use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;

class LaravelWorkflowEngine
{
    private WorkflowEngine $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Start a new workflow
     */
    public function start(array|string $definition, array $initialData = []): WorkflowInstance
    {
        return $this->engine->start($definition, $initialData);
    }

    /**
     * Resume an existing workflow
     */
    public function resume(string $instanceId): WorkflowInstance
    {
        return $this->engine->resume($instanceId);
    }

    /**
     * Get workflow instance
     */
    public function getInstance(string $instanceId): WorkflowInstance
    {
        return $this->engine->getInstance($instanceId);
    }

    /**
     * Get all workflow instances
     */
    public function getInstances(array $filters = []): array
    {
        return $this->engine->getInstances($filters);
    }

    /**
     * Cancel a workflow
     */
    public function cancel(string $instanceId): WorkflowInstance
    {
        return $this->engine->cancel($instanceId);
    }

    /**
     * Get the underlying engine
     */
    public function getEngine(): WorkflowEngine
    {
        return $this->engine;
    }
}
