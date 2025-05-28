<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class ReserveInventoryAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock inventory reservation
        $reservationId = 'res_'.uniqid();
        $context->setData('inventory.reservation_id', $reservationId);
        $context->setData('inventory.reserved', true);

        return new ActionResult(
            success: true,
            data: ['reservation_id' => $reservationId, 'status' => 'reserved']
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order') &&
               $context->getData('order.valid') === true &&
               ($context->getData('fraud.risk') ?? 0) < 0.7;
    }

    public function getName(): string
    {
        return 'Reserve Inventory';
    }

    public function getDescription(): string
    {
        return 'Reserves inventory items for the order';
    }
}
