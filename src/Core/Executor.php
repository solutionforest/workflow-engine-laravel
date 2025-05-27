<?php

namespace SolutionForest\WorkflowMastery\Core;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\Facades\Log;
use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Events\StepCompletedEvent;
use SolutionForest\WorkflowMastery\Events\StepFailedEvent;
use SolutionForest\WorkflowMastery\Events\WorkflowCompletedEvent;
use SolutionForest\WorkflowMastery\Events\WorkflowFailedEvent;

class Executor
{
    private StateManager $stateManager;

    private EventDispatcher $eventDispatcher;

    public function __construct(
        StateManager $stateManager,
        ?EventDispatcher $eventDispatcher = null
    ) {
        $this->stateManager = $stateManager;
        $this->eventDispatcher = $eventDispatcher ?? app(EventDispatcher::class);
    }

    public function execute(WorkflowInstance $instance): void
    {
        try {
            $this->processWorkflow($instance);
        } catch (\Exception $e) {
            Log::error('Workflow execution failed', [
                'workflow_id' => $instance->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->stateManager->setError($instance, $e->getMessage());
            $this->eventDispatcher->dispatch(new WorkflowFailedEvent($instance, $e));
        }
    }

    private function processWorkflow(WorkflowInstance $instance): void
    {
        // If workflow is not running, start it
        if ($instance->getState() === WorkflowState::PENDING) {
            $instance->setState(WorkflowState::RUNNING);
            $this->stateManager->save($instance);
        }

        // Get next steps to execute
        $nextSteps = $instance->getNextSteps();

        if (empty($nextSteps)) {
            // Workflow completed
            $instance->setState(WorkflowState::COMPLETED);
            $this->stateManager->save($instance);
            $this->eventDispatcher->dispatch(new WorkflowCompletedEvent($instance));

            return;
        }

        // Execute each next step
        foreach ($nextSteps as $step) {
            if ($instance->isStepCompleted($step->getId())) {
                continue; // Skip already completed steps
            }

            if (! $instance->canExecuteStep($step->getId())) {
                continue; // Skip steps that can't be executed yet
            }

            $this->executeStep($instance, $step);
        }
    }

    private function executeStep(WorkflowInstance $instance, Step $step): void
    {
        Log::info('Executing workflow step', [
            'workflow_id' => $instance->getId(),
            'step_id' => $step->getId(),
            'action' => $step->getActionClass(),
        ]);

        $instance->setCurrentStepId($step->getId());
        $this->stateManager->save($instance);

        try {
            if ($step->hasAction()) {
                $this->executeAction($instance, $step);
            }

            // Mark step as completed
            $this->stateManager->markStepCompleted($instance, $step->getId());
            $this->eventDispatcher->dispatch(new StepCompletedEvent($instance, $step));

            Log::info('Workflow step completed', [
                'workflow_id' => $instance->getId(),
                'step_id' => $step->getId(),
            ]);

            // Continue execution recursively
            $this->processWorkflow($instance);

        } catch (\Exception $e) {
            $errorMessage = "Step {$step->getId()} failed: {$e->getMessage()}";

            Log::error('Workflow step failed', [
                'workflow_id' => $instance->getId(),
                'step_id' => $step->getId(),
                'error' => $e->getMessage(),
            ]);

            $this->stateManager->markStepFailed($instance, $step->getId(), $errorMessage);
            $this->eventDispatcher->dispatch(new StepFailedEvent($instance, $step, $e));

            // For now, fail the entire workflow if any step fails
            // TODO: Implement retry logic and error handling strategies
            throw $e;
        }
    }

    private function executeAction(WorkflowInstance $instance, Step $step): void
    {
        $actionClass = $step->getActionClass();

        if (! class_exists($actionClass)) {
            throw new \InvalidArgumentException("Action class {$actionClass} does not exist");
        }

        $action = app($actionClass, ['config' => $step->getConfig()]);

        if (! $action instanceof WorkflowAction) {
            throw new \InvalidArgumentException("Action class {$actionClass} must implement WorkflowAction interface");
        }

        $context = new WorkflowContext(
            workflowId: $instance->getId(),
            stepId: $step->getId(),
            data: $instance->getData(),
            config: $step->getConfig()
        );

        $result = $action->execute($context);

        if ($result->isSuccess()) {
            // Merge any output data from the action
            if ($result->hasData()) {
                $instance->mergeData($result->getData());
                $this->stateManager->save($instance);
            }
        } else {
            throw new \RuntimeException($result->getErrorMessage() ?? 'Action failed');
        }
    }
}
