<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock payment processing
        $paymentId = 'pay_'.uniqid();
        $success = $order['total'] < 100000; // Simulate payment failure for very large orders

        if ($success) {
            $context->setData('payment.id', $paymentId);
            $context->setData('payment.success', true);
            $context->setData('payment.amount', $order['total']);
        } else {
            $context->setData('payment.success', false);
            $context->setData('payment.error', 'Payment declined');
        }

        return new ActionResult(
            success: $success,
            data: [
                'payment_id' => $success ? $paymentId : null,
                'amount' => $order['total'],
                'status' => $success ? 'completed' : 'failed',
            ],
            errorMessage: $success ? null : 'Payment processing failed'
        );
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order') &&
               $context->getData('inventory.reserved') === true;
    }

    public function getName(): string
    {
        return 'Process Payment';
    }

    public function getDescription(): string
    {
        return 'Processes payment for the order';
    }
}
