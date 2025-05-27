<?php

namespace SolutionForest\WorkflowMastery\Actions;

use Illuminate\Support\Facades\Log;
use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

abstract class BaseAction implements WorkflowAction
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function execute(WorkflowContext $context): ActionResult
    {
        Log::info('Executing action', [
            'action' => static::class,
            'workflow_id' => $context->getWorkflowId(),
            'step_id' => $context->getCurrentStepId(),
        ]);

        try {
            // Validate prerequisites
            if (! $this->canExecute($context)) {
                return ActionResult::failure('Prerequisites not met');
            }

            // Execute business logic
            $result = $this->doExecute($context);

            // Log success
            Log::info('Action completed successfully', [
                'action' => static::class,
                'result' => $result->getData(),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Action failed', [
                'action' => static::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ActionResult::failure($e->getMessage());
        }
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return true; // Default implementation allows execution
    }

    public function getName(): string
    {
        return class_basename(static::class);
    }

    public function getDescription(): string
    {
        return 'Base workflow action';
    }

    /**
     * Implement the actual action logic in this method
     */
    abstract protected function doExecute(WorkflowContext $context): ActionResult;

    /**
     * Get configuration value
     */
    protected function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get all configuration
     */
    protected function getAllConfig(): array
    {
        return $this->config;
    }
}
