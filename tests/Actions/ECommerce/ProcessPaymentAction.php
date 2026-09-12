<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests\Actions\ECommerce;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

class ProcessPaymentAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock payment processing
        $paymentId = 'pay_'.uniqid();
        $success = $order['total'] < 100000; // Simulate payment failure for very large orders

        // WorkflowContext is immutable, so what the step produces is returned
        // rather than written back into the context it was handed.
        return new ActionResult(
            success: $success,
            data: [
                'payment_id' => $success ? $paymentId : null,
                'amount' => $order['total'],
                'status' => $success ? 'completed' : 'failed',
                'payment' => [
                    'id' => $success ? $paymentId : null,
                    'success' => $success,
                    'amount' => $success ? $order['total'] : null,
                    'error' => $success ? null : 'Payment declined',
                ],
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
