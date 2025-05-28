<?php

namespace Tests\Actions\ECommerce;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;

class FraudCheckAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $order = $context->getData('order');

        // Mock fraud detection logic
        $riskScore = $this->calculateRiskScore($order);
        $context->setData('fraud.risk', $riskScore);

        return new ActionResult(
            success: true,
            data: ['risk_score' => $riskScore, 'status' => $riskScore < 0.7 ? 'safe' : 'flagged']
        );
    }

    private function calculateRiskScore(array $order): float
    {
        // Simple mock risk calculation
        $baseRisk = 0.1;

        if ($order['total'] > 10000) {
            $baseRisk += 0.3;
        }

        if ($order['total'] > 50000) {
            $baseRisk += 0.4;
        }

        return min($baseRisk, 1.0);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return $context->hasData('order') && $context->getData('order.valid') === true;
    }

    public function getName(): string
    {
        return 'Fraud Check';
    }

    public function getDescription(): string
    {
        return 'Analyzes order for potential fraud indicators';
    }
}
