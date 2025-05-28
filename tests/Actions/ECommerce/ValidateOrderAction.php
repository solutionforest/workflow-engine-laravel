<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class ValidateOrderAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock validation logic
        $isValid = isset($order['items']) &&
                  count($order['items']) > 0 &&
                  isset($order['total']) &&
                  $order['total'] > 0;

        $context->setData('order.valid', $isValid);

        return new ActionResult(
            success: $isValid,
            data: ['validation_result' => $isValid ? 'passed' : 'failed']
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order');
    }

    public function getName(): string
    {
        return 'Validate Order';
    }

    public function getDescription(): string
    {
        return 'Validates order data including items and total amount';
    }
}
