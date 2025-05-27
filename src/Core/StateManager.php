<?php

namespace SolutionForest\WorkflowMastery\Core;

use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;

class StateManager
{
    private StorageAdapter $storage;

    public function __construct(StorageAdapter $storage)
    {
        $this->storage = $storage;
    }

    public function save(WorkflowInstance $instance): void
    {
        $this->storage->save($instance);
    }

    public function load(string $instanceId): WorkflowInstance
    {
        if (! $this->storage->exists($instanceId)) {
            throw new \InvalidArgumentException("Workflow not found: {$instanceId}");
        }

        return $this->storage->load($instanceId);
    }

    public function updateState(WorkflowInstance $instance, WorkflowState $newState): void
    {
        $instance->setState($newState);
        $this->save($instance);
    }

    public function updateData(WorkflowInstance $instance, array $data): void
    {
        $instance->mergeData($data);
        $this->save($instance);
    }

    public function setCurrentStep(WorkflowInstance $instance, ?string $stepId): void
    {
        $instance->setCurrentStepId($stepId);
        $this->save($instance);
    }

    public function markStepCompleted(WorkflowInstance $instance, string $stepId): void
    {
        $instance->addCompletedStep($stepId);
        $this->save($instance);
    }

    public function markStepFailed(WorkflowInstance $instance, string $stepId, string $error): void
    {
        $instance->addFailedStep($stepId, $error);
        $this->save($instance);
    }

    public function setError(WorkflowInstance $instance, string $error): void
    {
        $instance->setErrorMessage($error);
        $instance->setState(WorkflowState::FAILED);
        $this->save($instance);
    }

    public function findInstances(array $criteria = []): array
    {
        return $this->storage->findInstances($criteria);
    }

    public function delete(string $instanceId): void
    {
        $this->storage->delete($instanceId);
    }

    public function exists(string $instanceId): bool
    {
        return $this->storage->exists($instanceId);
    }
}
