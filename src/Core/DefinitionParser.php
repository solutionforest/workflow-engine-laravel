<?php

namespace SolutionForest\WorkflowMastery\Core;

class DefinitionParser
{
    public function parse(array|string $definition): WorkflowDefinition
    {
        if (is_string($definition)) {
            $definition = json_decode($definition, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON definition: '.json_last_error_msg());
            }
        }

        $this->validateDefinition($definition);

        // Normalize steps before creating WorkflowDefinition
        $definition['steps'] = $this->normalizeSteps($definition['steps']);

        return WorkflowDefinition::fromArray($definition);
    }

    private function validateDefinition(array $definition): void
    {
        if (! isset($definition['name'])) {
            throw new \InvalidArgumentException('Workflow definition must have a name');
        }

        if (! isset($definition['steps']) || ! is_array($definition['steps'])) {
            throw new \InvalidArgumentException('Workflow definition must have steps array');
        }

        if (empty($definition['steps'])) {
            throw new \InvalidArgumentException('Workflow definition must have at least one step');
        }

        // Normalize steps format - convert sequential array to associative if needed
        $steps = $this->normalizeSteps($definition['steps']);

        // Validate each step
        foreach ($steps as $stepId => $stepData) {
            $this->validateStep($stepId, $stepData);
        }

        // Validate transitions if present
        if (isset($definition['transitions']) && is_array($definition['transitions'])) {
            foreach ($definition['transitions'] as $transition) {
                $this->validateTransition($transition, $steps);
            }
        }
    }

    private function normalizeSteps(array $steps): array
    {
        // If steps is already associative, return as-is
        if (! array_is_list($steps)) {
            return $steps;
        }

        // Convert sequential array with 'id' property to associative
        $normalizedSteps = [];
        foreach ($steps as $step) {
            if (! isset($step['id'])) {
                throw new \InvalidArgumentException('Step must have an id property');
            }
            $stepId = $step['id'];
            unset($step['id']); // Remove id from step data since it's now the key
            $normalizedSteps[$stepId] = $step;
        }

        return $normalizedSteps;
    }

    private function validateStep(string $stepId, array $stepData): void
    {
        if (empty($stepId)) {
            throw new \InvalidArgumentException('Step ID cannot be empty');
        }

        // Action is optional for some step types (like conditions or manual steps)
        if (isset($stepData['action']) && ! is_string($stepData['action'])) {
            throw new \InvalidArgumentException("Step {$stepId} action must be a string");
        }

        // Validate timeout format if present
        if (isset($stepData['timeout']) && ! $this->isValidTimeout($stepData['timeout'])) {
            throw new \InvalidArgumentException("Step {$stepId} has invalid timeout format");
        }

        // Validate retry attempts
        if (isset($stepData['retry_attempts']) && (! is_int($stepData['retry_attempts']) || $stepData['retry_attempts'] < 0)) {
            throw new \InvalidArgumentException("Step {$stepId} retry_attempts must be a non-negative integer");
        }
    }

    private function validateTransition(array $transition, array $steps): void
    {
        if (! isset($transition['from']) || ! isset($transition['to'])) {
            throw new \InvalidArgumentException('Transition must have both from and to fields');
        }

        if (! isset($steps[$transition['from']])) {
            throw new \InvalidArgumentException("Transition references unknown step: {$transition['from']}");
        }

        if (! isset($steps[$transition['to']])) {
            throw new \InvalidArgumentException("Transition references unknown step: {$transition['to']}");
        }
    }

    private function isValidTimeout(string $timeout): bool
    {
        // Valid formats: "30s", "5m", "2h", "1d"
        return preg_match('/^\d+[smhd]$/', $timeout) === 1;
    }
}
