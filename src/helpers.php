<?php

declare(strict_types=1);

use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

if (! function_exists('workflow')) {
    /**
     * Get the workflow engine instance from the Laravel container.
     */
    function workflow(): WorkflowEngine
    {
        return app(WorkflowEngine::class);
    }
}

if (! function_exists('start_workflow')) {
    /**
     * Start a new workflow.
     */
    function start_workflow(string $id, array $definition, array $context = []): string
    {
        return workflow()->start($id, $definition, $context);
    }
}

if (! function_exists('get_workflow')) {
    /**
     * Get a workflow instance by ID.
     */
    function get_workflow(string $id): WorkflowInstance
    {
        return workflow()->getInstance($id);
    }
}

if (! function_exists('cancel_workflow')) {
    /**
     * Cancel a workflow.
     */
    function cancel_workflow(string $id, string $reason = 'Cancelled'): void
    {
        workflow()->cancel($id, $reason);
    }
}

if (! function_exists('list_workflows')) {
    /**
     * List workflows.
     */
    function list_workflows(array $filters = []): array
    {
        return workflow()->getInstances($filters);
    }
}
