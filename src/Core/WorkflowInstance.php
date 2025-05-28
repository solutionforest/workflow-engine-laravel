<?php

namespace SolutionForest\WorkflowMastery\Core;

use Carbon\Carbon;

class WorkflowInstance
{
    private WorkflowState $state;

    private array $data;

    private ?string $currentStepId = null;

    private array $completedSteps = [];

    private array $failedSteps = [];

    private ?string $errorMessage = null;

    private readonly Carbon $createdAt;

    private Carbon $updatedAt;

    public function __construct(
        private readonly string $id,
        private readonly WorkflowDefinition $definition,
        WorkflowState $state,
        array $data = [],
        ?Carbon $createdAt = null,
        ?Carbon $updatedAt = null
    ) {
        $this->state = $state;
        $this->data = $data;
        $this->createdAt = $createdAt ?? now();
        $this->updatedAt = $updatedAt ?? now();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDefinition(): WorkflowDefinition
    {
        return $this->definition;
    }

    public function getState(): WorkflowState
    {
        return $this->state;
    }

    public function setState(WorkflowState $state): void
    {
        $this->state = $state;
        $this->updatedAt = now();
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->updatedAt = now();
    }

    public function mergeData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
        $this->updatedAt = now();
    }

    public function getCurrentStepId(): ?string
    {
        return $this->currentStepId;
    }

    public function setCurrentStepId(?string $stepId): void
    {
        $this->currentStepId = $stepId;
        $this->updatedAt = now();
    }

    public function getCompletedSteps(): array
    {
        return $this->completedSteps;
    }

    public function addCompletedStep(string $stepId): void
    {
        if (! in_array($stepId, $this->completedSteps)) {
            $this->completedSteps[] = $stepId;
            $this->updatedAt = now();
        }
    }

    public function getFailedSteps(): array
    {
        return $this->failedSteps;
    }

    public function addFailedStep(string $stepId, string $error): void
    {
        $this->failedSteps[] = [
            'step_id' => $stepId,
            'error' => $error,
            'failed_at' => now()->toISOString(),
        ];
        $this->updatedAt = now();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->updatedAt = now();
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function isStepCompleted(string $stepId): bool
    {
        return in_array($stepId, $this->completedSteps);
    }

    public function getNextSteps(): array
    {
        return $this->definition->getNextSteps($this->currentStepId, $this->data);
    }

    public function canExecuteStep(string $stepId): bool
    {
        $step = $this->definition->getStep($stepId);
        if (! $step) {
            return false;
        }

        // Check if all prerequisites are met
        foreach ($step->getPrerequisites() as $prerequisite) {
            if (! $this->isStepCompleted($prerequisite)) {
                return false;
            }
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'definition_name' => $this->definition->getName(),
            'definition_version' => $this->definition->getVersion(),
            'state' => $this->state->value,
            'data' => $this->data,
            'current_step_id' => $this->currentStepId,
            'completed_steps' => $this->completedSteps,
            'failed_steps' => $this->failedSteps,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt->toISOString(),
            'updated_at' => $this->updatedAt->toISOString(),
        ];
    }

    public static function fromArray(array $data, WorkflowDefinition $definition): self
    {
        $instance = new self(
            id: $data['id'],
            definition: $definition,
            state: WorkflowState::from($data['state']),
            data: $data['data'] ?? [],
            createdAt: Carbon::parse($data['created_at']),
            updatedAt: Carbon::parse($data['updated_at'])
        );

        $instance->currentStepId = $data['current_step_id'] ?? null;
        $instance->completedSteps = $data['completed_steps'] ?? [];
        $instance->failedSteps = $data['failed_steps'] ?? [];
        $instance->errorMessage = $data['error_message'] ?? null;

        return $instance;
    }

    /**
     * Get workflow progress as percentage
     */
    public function getProgress(): float
    {
        $totalSteps = count($this->definition->getSteps());
        if ($totalSteps === 0) {
            return 100.0;
        }

        $completedSteps = count($this->completedSteps);

        return ($completedSteps / $totalSteps) * 100.0;
    }

    /**
     * Get context (alias for getData for compatibility)
     */
    public function getContext(): WorkflowContext
    {
        return new WorkflowContext(
            $this->id,
            $this->currentStepId ?? '',
            $this->data
        );
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return $this->definition->getName();
    }
}
