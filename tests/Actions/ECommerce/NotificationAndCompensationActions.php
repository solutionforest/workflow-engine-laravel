<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class SendOrderConfirmationAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');
        $shipment = $context->getData('shipment');

        // Mock notification sending
        $notificationId = 'notif_'.uniqid();

        $context->setData('notification.id', $notificationId);
        $context->setData('notification.sent', true);
        $context->setData('notification.type', 'order_confirmation');

        return new ActionResult(
            success: true,
            data: [
                'notification_id' => $notificationId,
                'recipient' => $order['customer_email'] ?? 'customer@example.com',
                'tracking_number' => $shipment['tracking_number'] ?? null,
                'status' => 'sent',
            ]
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order') &&
               $context->getData('shipment.created') === true;
    }

    public function getName(): string
    {
        return 'Send Order Confirmation';
    }

    public function getDescription(): string
    {
        return 'Sends order confirmation email to customer';
    }
}

// Compensation Actions
class ReleaseInventoryAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $reservationId = $context->getData('inventory.reservation_id');

        if ($reservationId) {
            $context->setData('inventory.reserved', false);
            $context->setData('inventory.released', true);
        }

        return new ActionResult(
            success: true,
            data: ['reservation_id' => $reservationId, 'status' => 'released']
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('inventory.reservation_id');
    }

    public function getName(): string
    {
        return 'Release Inventory';
    }

    public function getDescription(): string
    {
        return 'Releases previously reserved inventory';
    }
}

class RefundPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $paymentId = $context->getData('payment.id');
        $amount = $context->getData('payment.amount');

        if ($paymentId) {
            $refundId = 'ref_'.uniqid();
            $context->setData('refund.id', $refundId);
            $context->setData('refund.amount', $amount);
            $context->setData('refund.processed', true);
        }

        return new ActionResult(
            success: true,
            data: [
                'refund_id' => $refundId ?? null,
                'amount' => $amount,
                'status' => 'processed',
            ]
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('payment.id');
    }

    public function getName(): string
    {
        return 'Refund Payment';
    }

    public function getDescription(): string
    {
        return 'Processes payment refund';
    }
}
