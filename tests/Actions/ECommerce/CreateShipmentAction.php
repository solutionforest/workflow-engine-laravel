<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class CreateShipmentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock shipment creation
        $shipmentId = 'ship_'.uniqid();
        $trackingNumber = 'TRK'.mt_rand(100000, 999999);

        $context->setData('shipment.id', $shipmentId);
        $context->setData('shipment.tracking_number', $trackingNumber);
        $context->setData('shipment.created', true);

        return new ActionResult(
            success: true,
            data: [
                'shipment_id' => $shipmentId,
                'tracking_number' => $trackingNumber,
                'status' => 'created',
            ]
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order') &&
               $context->getData('payment.success') === true;
    }

    public function getName(): string
    {
        return 'Create Shipment';
    }

    public function getDescription(): string
    {
        return 'Creates shipment and generates tracking number';
    }
}
