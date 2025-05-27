<?php

namespace SolutionForest\WorkflowMastery\Core;

use SolutionForest\WorkflowMastery\Actions\DelayAction;
use SolutionForest\WorkflowMastery\Actions\LogAction;

class ActionResolver
{
    private const ACTION_MAP = [
        'log' => LogAction::class,
        'delay' => DelayAction::class,
    ];

    /**
     * Resolve action name to action class
     */
    public static function resolve(string $actionName): string
    {
        // If it's already a full class name, return as-is
        if (class_exists($actionName)) {
            return $actionName;
        }

        // Check our action map
        if (isset(self::ACTION_MAP[$actionName])) {
            return self::ACTION_MAP[$actionName];
        }

        // Try to construct a class name from the action name
        $className = 'SolutionForest\\WorkflowMastery\\Actions\\'.ucfirst($actionName).'Action';
        if (class_exists($className)) {
            return $className;
        }

        throw new \InvalidArgumentException("Unknown action: {$actionName}");
    }

    /**
     * Get all available actions
     */
    public static function getAvailableActions(): array
    {
        return self::ACTION_MAP;
    }
}
