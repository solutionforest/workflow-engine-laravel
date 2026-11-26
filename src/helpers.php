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
     *
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $context
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

if (! function_exists('signal_workflow')) {
    /**
     * Deliver a signal to a suspended workflow (see WorkflowEngine::signal()).
     *
     * @param  array<string, mixed>  $payload
     */
    function signal_workflow(string $id, string $signal, array $payload = []): WorkflowInstance
    {
        return workflow()->signal($id, $signal, $payload);
    }
}

if (! function_exists('list_workflows')) {
    /**
     * List workflows.
     *
     * @param  array<string, mixed>  $filters
     * @return array<WorkflowInstance>
     */
    function list_workflows(array $filters = []): array
    {
        return workflow()->getInstances($filters);
    }
}
