<?php

use SolutionForest\WorkflowMastery\Core\WorkflowDefinition;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;
use SolutionForest\WorkflowMastery\Core\WorkflowInstance;

if (! function_exists('workflow')) {
    /**
     * Get the workflow engine instance
     */
    function workflow(): WorkflowEngine
    {
        return app(WorkflowEngine::class);
    }
}

if (! function_exists('start_workflow')) {
    /**
     * Start a new workflow
     */
    function start_workflow(string $workflowId, array $definition, array $context = []): string
    {
        return workflow()->start($workflowId, $definition, $context);
    }
}

if (! function_exists('resume_workflow')) {
    /**
     * Resume an existing workflow
     */
    function resume_workflow(string $instanceId): WorkflowInstance
    {
        return workflow()->resume($instanceId);
    }
}

if (! function_exists('get_workflow')) {
    /**
     * Get a workflow instance
     */
    function get_workflow(string $instanceId): WorkflowInstance
    {
        return workflow()->getInstance($instanceId);
    }
}

if (! function_exists('cancel_workflow')) {
    /**
     * Cancel a workflow
     */
    function cancel_workflow(string $instanceId, string $reason = ''): WorkflowInstance
    {
        return workflow()->cancel($instanceId, $reason);
    }
}

if (! function_exists('workflow_definition')) {
    /**
     * Create a workflow definition from array
     */
    function workflow_definition(array $definition): WorkflowDefinition
    {
        return WorkflowDefinition::fromArray($definition);
    }
}
