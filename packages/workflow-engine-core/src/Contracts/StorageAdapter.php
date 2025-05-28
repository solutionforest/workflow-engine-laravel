<?php

namespace SolutionForest\WorkflowEngine\Contracts;

use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

interface StorageAdapter
{
    /**
     * Save a workflow instance
     */
    public function save(WorkflowInstance $instance): void;

    /**
     * Load a workflow instance by ID
     */
    public function load(string $id): WorkflowInstance;

    /**
     * Find workflow instances by criteria
     */
    public function findInstances(array $criteria = []): array;

    /**
     * Delete a workflow instance
     */
    public function delete(string $id): void;

    /**
     * Check if a workflow instance exists
     */
    public function exists(string $id): bool;

    /**
     * Update workflow instance state
     */
    public function updateState(string $id, array $updates): void;
}
