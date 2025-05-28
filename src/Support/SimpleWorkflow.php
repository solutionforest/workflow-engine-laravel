<?php

namespace SolutionForest\WorkflowMastery\Support;

use Illuminate\Contracts\Events\Dispatcher;
use SolutionForest\WorkflowMastery\Contracts\StorageAdapter;
use SolutionForest\WorkflowMastery\Core\WorkflowBuilder;
use SolutionForest\WorkflowMastery\Core\WorkflowEngine;

/**
 * Simple workflow helper for quick workflow creation and execution
 *
 * This class provides a simplified API for common workflow operations,
 * reducing the learning curve for new users.
 */
class SimpleWorkflow
{
    private WorkflowEngine $engine;

    public function __construct(StorageAdapter $storage, ?Dispatcher $eventDispatcher = null)
    {
        $this->engine = new WorkflowEngine($storage, $eventDispatcher);
    }

    /**
     * Create a simple sequential workflow
     *
     * @example
     * SimpleWorkflow::sequential('user-onboarding', [
     *     SendWelcomeEmailAction::class,
     *     CreateUserProfileAction::class,
     *     AssignDefaultRoleAction::class,
     * ], ['user_id' => 123])
     */
    public static function sequential(string $name, array $actions, array $context = []): string
    {
        $builder = WorkflowBuilder::create($name);

        foreach ($actions as $action) {
            $builder->then($action);
        }

        $workflow = $builder->build();
        $engine = app(WorkflowEngine::class);

        return $engine->start($name.'-'.uniqid(), $workflow->toArray(), $context);
    }

    /**
     * Create a workflow with conditions
     *
     * @example
     * SimpleWorkflow::conditional('order-processing', [
     *     'validate' => ValidateOrderAction::class,
     *     'charge' => ChargePaymentAction::class,
     *     'if payment.success' => [
     *         'fulfill' => FulfillOrderAction::class,
     *         'email' => SendConfirmationEmailAction::class,
     *     ],
     *     'else' => [
     *         'cancel' => CancelOrderAction::class,
     *     ]
     * ])
     */
    public static function conditional(string $name, array $definition, array $context = []): string
    {
        $builder = WorkflowBuilder::create($name);

        foreach ($definition as $key => $value) {
            if (str_starts_with($key, 'if ')) {
                $condition = substr($key, 3);
                $builder->when($condition, function ($b) use ($value) {
                    foreach ($value as $action) {
                        $b->then($action);
                    }
                });
            } elseif ($key === 'else') {
                // Handle else case - would need more sophisticated logic
                foreach ($value as $action) {
                    $builder->then($action);
                }
            } else {
                $builder->then($value);
            }
        }

        $workflow = $builder->build();
        $engine = app(WorkflowEngine::class);

        return $engine->start($name.'-'.uniqid(), $workflow->toArray(), $context);
    }

    /**
     * Create workflow from quick templates
     *
     * @example
     * SimpleWorkflow::quick()->userOnboarding()->start(['user_id' => 123])
     */
    public static function quick(): QuickWorkflowStarter
    {
        return new QuickWorkflowStarter;
    }

    /**
     * Run a single action as a workflow
     *
     * @example
     * SimpleWorkflow::runAction(SendEmailAction::class, [
     *     'to' => 'user@example.com',
     *     'subject' => 'Welcome!'
     * ])
     */
    public static function runAction(string $actionClass, array $context = []): string
    {
        return self::sequential(
            'single-action-'.class_basename($actionClass),
            [$actionClass],
            $context
        );
    }
}

/**
 * Quick workflow starter for fluent template usage
 */
class QuickWorkflowStarter
{
    public function userOnboarding(): QuickWorkflowInstance
    {
        return new QuickWorkflowInstance(
            WorkflowBuilder::quick()->userOnboarding()
        );
    }

    public function orderProcessing(): QuickWorkflowInstance
    {
        return new QuickWorkflowInstance(
            WorkflowBuilder::quick()->orderProcessing()
        );
    }

    public function documentApproval(): QuickWorkflowInstance
    {
        return new QuickWorkflowInstance(
            WorkflowBuilder::quick()->documentApproval()
        );
    }
}

/**
 * Quick workflow instance for immediate execution
 */
class QuickWorkflowInstance
{
    public function __construct(private WorkflowBuilder $builder) {}

    public function start(array $context = []): string
    {
        $workflow = $this->builder->build();
        $engine = app(WorkflowEngine::class);

        return $engine->start(
            $workflow->getName().'-'.uniqid(),
            $workflow->toArray(),
            $context
        );
    }

    public function customize(callable $callback): self
    {
        $callback($this->builder);

        return $this;
    }
}
