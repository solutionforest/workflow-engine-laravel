<?php

namespace SolutionForest\WorkflowMastery\Examples;

use SolutionForest\WorkflowMastery\Actions\BaseAction;
use SolutionForest\WorkflowMastery\Attributes\Condition;
use SolutionForest\WorkflowMastery\Attributes\Retry;
use SolutionForest\WorkflowMastery\Attributes\Timeout;
use SolutionForest\WorkflowMastery\Attributes\WorkflowStep;
use SolutionForest\WorkflowMastery\Core\ActionResult;
use SolutionForest\WorkflowMastery\Core\WorkflowBuilder;
use SolutionForest\WorkflowMastery\Core\WorkflowContext;
use SolutionForest\WorkflowMastery\Support\SimpleWorkflow;

/**
 * Examples showcasing PHP 8.3+ features and simplified API
 */
class ModernWorkflowExamples
{
    /**
     * Example 1: Fluent API with named arguments
     */
    public function fluentWorkflowExample(): string
    {
        // Using the new fluent builder API
        $workflow = WorkflowBuilder::create('modern-user-onboarding')
            ->description('Modern user onboarding with PHP 8.3+ features')
            ->version('2.0')

            // Email step with template variables
            ->email(
                template: 'welcome',
                to: '{{ user.email }}',
                subject: 'Welcome to {{ app.name }}!',
                data: ['welcome_bonus' => 50]
            )

            // Delay with named arguments
            ->delay(minutes: 5)

            // HTTP request to external service
            ->http(
                url: 'https://api.analytics.com/track',
                method: 'POST',
                data: [
                    'event' => 'user_registered',
                    'user_id' => '{{ user.id }}',
                    'timestamp' => '{{ now() }}',
                ]
            )

            // Conditional logic
            ->when('user.premium', function ($builder) {
                $builder->email(
                    template: 'premium-welcome',
                    to: '{{ user.email }}',
                    subject: 'Welcome to Premium!'
                );
            })

            ->build();

        // Start with named arguments
        $engine = app(\SolutionForest\WorkflowMastery\Core\WorkflowEngine::class);

        return $engine->start(
            workflowId: 'onboarding-'.uniqid(),
            definition: $workflow->toArray(),
            context: ['user' => ['id' => 123, 'email' => 'user@example.com', 'premium' => true]]
        );
    }

    /**
     * Example 2: Quick templates for common patterns
     */
    public function quickTemplateExample(): string
    {
        // Super simple API for common workflows
        return SimpleWorkflow::quick()
            ->userOnboarding()
            ->customize(fn ($builder) => $builder->delay(hours: 1) // Add custom delay
                ->email(
                    template: 'follow-up',
                    to: '{{ user.email }}',
                    subject: 'How are you finding the app?'
                )
            )
            ->start(['user' => ['id' => 123, 'email' => 'user@example.com']]);
    }

    /**
     * Example 3: Sequential workflow with one line
     */
    public function sequentialExample(): string
    {
        return SimpleWorkflow::sequential(
            name: 'order-fulfillment',
            actions: [
                ValidateOrderAction::class,
                ChargePaymentAction::class,
                UpdateInventoryAction::class,
                ShipOrderAction::class,
            ],
            context: ['order_id' => 12345]
        );
    }

    /**
     * Example 4: Conditional workflow
     */
    public function conditionalExample(): string
    {
        return SimpleWorkflow::conditional('payment-processing', [
            'validate' => ValidatePaymentAction::class,
            'charge' => ChargeCardAction::class,
            'if payment.success' => [
                'confirm' => ConfirmOrderAction::class,
                'email' => SendReceiptAction::class,
            ],
            'else' => [
                'retry' => RetryPaymentAction::class,
            ],
        ], ['payment' => ['amount' => 99.99, 'method' => 'card']]);
    }

    /**
     * Example 5: Single action execution
     */
    public function singleActionExample(): string
    {
        return SimpleWorkflow::runAction(
            actionClass: SendEmailAction::class,
            context: [
                'to' => 'admin@example.com',
                'subject' => 'Test Email',
                'body' => 'This is a test email sent via workflow.',
            ]
        );
    }
}

/**
 * Example action using PHP 8.3+ attributes
 */
#[WorkflowStep(
    id: 'validate_order',
    name: 'Validate Order',
    description: 'Validates order data and checks inventory'
)]
#[Timeout(seconds: 30)]
#[Retry(attempts: 3, backoff: 'exponential')]
#[Condition('order.amount > 0')]
#[Condition('order.items is not empty')]
class ValidateOrderAction extends BaseAction
{
    public function getName(): string
    {
        return 'Validate Order';
    }

    public function getDescription(): string
    {
        return 'Validates order data and checks inventory availability';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        $orderData = $context->getData()['order'] ?? [];

        // Validation logic using PHP 8.3+ match expressions
        $validationResult = match (true) {
            empty($orderData) => ['valid' => false, 'error' => 'Order data is missing'],
            ($orderData['amount'] ?? 0) <= 0 => ['valid' => false, 'error' => 'Invalid order amount'],
            empty($orderData['items']) => ['valid' => false, 'error' => 'No items in order'],
            default => ['valid' => true, 'error' => null]
        };

        if (! $validationResult['valid']) {
            return ActionResult::failure($validationResult['error']);
        }

        return ActionResult::success([
            'validated_order' => $orderData,
            'validation_timestamp' => now()->toISOString(),
        ]);
    }
}

// Additional example actions
class ChargePaymentAction extends BaseAction
{
    public function getName(): string
    {
        return 'Charge Payment';
    }

    public function getDescription(): string
    {
        return 'Charges the customer payment method';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        // Implementation would go here
        return ActionResult::success(['payment_status' => 'charged']);
    }
}

class UpdateInventoryAction extends BaseAction
{
    public function getName(): string
    {
        return 'Update Inventory';
    }

    public function getDescription(): string
    {
        return 'Updates inventory levels';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['inventory_updated' => true]);
    }
}

class ShipOrderAction extends BaseAction
{
    public function getName(): string
    {
        return 'Ship Order';
    }

    public function getDescription(): string
    {
        return 'Initiates order shipping';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['tracking_number' => 'TRK'.rand(100000, 999999)]);
    }
}

class SendEmailAction extends BaseAction
{
    public function getName(): string
    {
        return 'Send Email';
    }

    public function getDescription(): string
    {
        return 'Sends an email';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['email_sent' => true]);
    }
}

// Additional example actions for conditional workflow
class ValidatePaymentAction extends BaseAction
{
    public function getName(): string
    {
        return 'Validate Payment';
    }

    public function getDescription(): string
    {
        return 'Validates payment information';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['payment_valid' => true]);
    }
}

class ChargeCardAction extends BaseAction
{
    public function getName(): string
    {
        return 'Charge Card';
    }

    public function getDescription(): string
    {
        return 'Charges the credit card';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['payment' => ['success' => true]]);
    }
}

class ConfirmOrderAction extends BaseAction
{
    public function getName(): string
    {
        return 'Confirm Order';
    }

    public function getDescription(): string
    {
        return 'Confirms the order';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['order_confirmed' => true]);
    }
}

class SendReceiptAction extends BaseAction
{
    public function getName(): string
    {
        return 'Send Receipt';
    }

    public function getDescription(): string
    {
        return 'Sends payment receipt';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['receipt_sent' => true]);
    }
}

class RetryPaymentAction extends BaseAction
{
    public function getName(): string
    {
        return 'Retry Payment';
    }

    public function getDescription(): string
    {
        return 'Retries failed payment';
    }

    protected function doExecute(WorkflowContext $context): ActionResult
    {
        return ActionResult::success(['payment_retried' => true]);
    }
}
