<?php

namespace SolutionForest\WorkflowMastery\Core;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Log;
use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;
use SolutionForest\WorkflowMastery\Events\WorkflowCancelled;
use SolutionForest\WorkflowMastery\Events\WorkflowStarted;

class WorkflowEngine
{
    private DefinitionParser $parser;

    private StateManager $stateManager;

    private Executor $executor;

    private EventDispatcher $eventDispatcher;

    private StorageAdapter $storage;

    public function __construct(
        StorageAdapter $storage,
        ?EventDispatcher $eventDispatcher = null
    ) {
        $this->storage = $storage;
        $this->parser = new DefinitionParser;
        $this->stateManager = new StateManager($storage);
        $this->executor = new Executor($this->stateManager);
        $this->eventDispatcher = $eventDispatcher ?? app(EventDispatcher::class);
    }

    /**
     * Start a new workflow instance
     */
    public function start(string $workflowId, array $definition, array $context = []): string
    {
        // Parse definition
        $workflowDef = $this->parser->parse($definition);

        // Create instance
        $instance = new WorkflowInstance(
            id: $workflowId,
            definition: $workflowDef,
            state: WorkflowState::PENDING,
            data: $context,
            createdAt: now(),
            updatedAt: now()
        );

        // Save initial state
        $this->stateManager->save($instance);

        // Dispatch start event
        $this->eventDispatcher->dispatch(new WorkflowStarted(
            $instance->getId(),
            $instance->getDefinition()->getName(),
            $context
        ));

        // Execute first step
        $this->executor->execute($instance);

        return $instance->getId();
    }

    /**
     * Resume an existing workflow instance
     */
    public function resume(string $instanceId): WorkflowInstance
    {
        $instance = $this->stateManager->load($instanceId);

        if ($instance->getState() === WorkflowState::COMPLETED) {
            throw new \InvalidArgumentException('Cannot resume completed workflow');
        }

        $this->executor->execute($instance);

        return $instance;
    }

    /**
     * Get workflow instance by ID
     */
    public function getInstance(string $instanceId): WorkflowInstance
    {
        return $this->stateManager->load($instanceId);
    }

    /**
     * Get all workflow instances with optional filters
     */
    public function getInstances(array $filters = []): array
    {
        return $this->storage->findInstances($filters);
    }

    /**
     * Cancel a workflow instance
     */
    public function cancel(string $instanceId, string $reason = ''): WorkflowInstance
    {
        $instance = $this->stateManager->load($instanceId);
        $instance->setState(WorkflowState::CANCELLED);
        $this->stateManager->save($instance);

        // Dispatch cancel event
        $this->eventDispatcher->dispatch(new WorkflowCancelled(
            $instance->getId(),
            $instance->getDefinition()->getName(),
            $reason
        ));

        return $instance;
    }

    /**
     * Get workflow instance by ID
     */
    public function getWorkflow(string $workflowId): WorkflowInstance
    {
        $instance = $this->stateManager->load($workflowId);
        if (! $instance) {
            throw new \InvalidArgumentException("Workflow not found: {$workflowId}");
        }

        return $instance;
    }

    /**
     * Get workflow status
     */
    public function getStatus(string $workflowId): array
    {
        $instance = $this->getWorkflow($workflowId);

        return [
            'workflow_id' => $instance->getId(),
            'name' => $instance->getDefinition()->getName(),
            'state' => $instance->getState()->value,
            'current_step' => $instance->getCurrentStepId(),
            'progress' => $instance->getProgress(),
            'created_at' => $instance->getCreatedAt(),
            'updated_at' => $instance->getUpdatedAt(),
        ];
    }

    /**
     * List workflows with optional filters
     */
    public function listWorkflows(array $filters = []): array
    {
        // Convert WorkflowState enum to string value for storage layer
        if (isset($filters['state']) && $filters['state'] instanceof WorkflowState) {
            $filters['state'] = $filters['state']->value;
        }

        $instances = $this->storage->findInstances($filters);

        return array_map(function (WorkflowInstance $instance) {
            return [
                'workflow_id' => $instance->getId(),
                'name' => $instance->getDefinition()->getName(),
                'state' => $instance->getState()->value,
                'current_step' => $instance->getCurrentStepId(),
                'progress' => $instance->getProgress(),
                'created_at' => $instance->getCreatedAt(),
                'updated_at' => $instance->getUpdatedAt(),
            ];
        }, $instances);
    }
}
