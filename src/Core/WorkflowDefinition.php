<?php

namespace SolutionForest\WorkflowMastery\Core;

class WorkflowDefinition
{
    private string $name;

    private string $version;

    private array $steps;

    private array $transitions;

    private array $metadata;

    public function __construct(
        string $name,
        string $version,
        array $steps = [],
        array $transitions = [],
        array $metadata = []
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->steps = $this->processSteps($steps);
        $this->transitions = $transitions;
        $this->metadata = $metadata;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getStep(string $id): ?Step
    {
        return $this->steps[$id] ?? null;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getFirstStep(): ?Step
    {
        // Find step with no incoming transitions
        $stepsWithIncoming = [];
        foreach ($this->transitions as $transition) {
            $stepsWithIncoming[] = $transition['to'];
        }

        foreach ($this->steps as $step) {
            if (! in_array($step->getId(), $stepsWithIncoming)) {
                return $step;
            }
        }

        // If no step found without incoming transitions, return first step
        return reset($this->steps) ?: null;
    }

    public function getNextSteps(?string $currentStepId, array $data = []): array
    {
        if ($currentStepId === null) {
            $firstStep = $this->getFirstStep();

            return $firstStep ? [$firstStep] : [];
        }

        $nextSteps = [];
        foreach ($this->transitions as $transition) {
            if ($transition['from'] === $currentStepId) {
                // Check condition if present
                if (isset($transition['condition']) && ! $this->evaluateCondition($transition['condition'], $data)) {
                    continue;
                }

                $nextStep = $this->getStep($transition['to']);
                if ($nextStep) {
                    $nextSteps[] = $nextStep;
                }
            }
        }

        return $nextSteps;
    }

    public function hasStep(string $stepId): bool
    {
        return isset($this->steps[$stepId]);
    }

    public function isLastStep(string $stepId): bool
    {
        // Check if this step has any outgoing transitions
        foreach ($this->transitions as $transition) {
            if ($transition['from'] === $stepId) {
                return false;
            }
        }

        return true;
    }

    private function processSteps(array $stepsData): array
    {
        $steps = [];
        foreach ($stepsData as $index => $stepData) {
            // Use the 'id' field from step data, or fall back to array index
            $stepId = $stepData['id'] ?? $index;

            $actionClass = null;
            if (isset($stepData['action'])) {
                $actionClass = ActionResolver::resolve($stepData['action']);
            }

            $steps[$stepId] = new Step(
                id: $stepId,
                actionClass: $actionClass,
                config: $stepData['parameters'] ?? $stepData['config'] ?? [],
                timeout: $stepData['timeout'] ?? null,
                retryAttempts: $stepData['retry_attempts'] ?? 0,
                compensationAction: $stepData['compensation'] ?? null,
                conditions: $stepData['conditions'] ?? [],
                prerequisites: $stepData['prerequisites'] ?? []
            );
        }

        return $steps;
    }

    private function evaluateCondition(string $condition, array $data): bool
    {
        // Simple condition evaluation - can be enhanced later
        // For now, support basic comparisons like "key === value"
        if (preg_match('/(\w+(?:\.\w+)*)\s*(===|!==|==|!=|>=|<=|>|<)\s*(.+)/', $condition, $matches)) {
            $key = $matches[1];
            $operator = $matches[2];
            $value = trim($matches[3], '"\'');

            $dataValue = data_get($data, $key);

            return match ($operator) {
                '===' => $dataValue === $value,
                '!==', '!==' => $dataValue !== $value,
                '==' => $dataValue == $value,
                '!=' => $dataValue != $value,
                '>' => $dataValue > $value,
                '<' => $dataValue < $value,
                '>=' => $dataValue >= $value,
                '<=' => $dataValue <= $value,
                default => false,
            };
        }

        return false; // Default to false if condition cannot be parsed
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'steps' => array_map(fn ($step) => $step->toArray(), $this->steps),
            'transitions' => $this->transitions,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            version: $data['version'] ?? '1.0',
            steps: $data['steps'] ?? [],
            transitions: $data['transitions'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }
}
