<?php

namespace SolutionForest\WorkflowMastery\Contracts;

use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

interface WorkflowAction
{
    /**
     * Execute the workflow action
     */
    public function execute(WorkflowContext $context): ActionResult;

    /**
     * Check if this action can be executed with the given context
     */
    public function canExecute(WorkflowContext $context): bool;

    /**
     * Get the display name for this action
     */
    public function getName(): string;

    /**
     * Get the description for this action
     */
    public function getDescription(): string;
}
