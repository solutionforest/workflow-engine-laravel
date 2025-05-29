<?php

namespace SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions;

use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;

class SendWelcomeEmailAction implements WorkflowAction
{
    public function execute(WorkflowContext $context): ActionResult
    {
        $config = $context->getConfig();
        $userData = $context->getData('user') ?? [];

        // Mock email sending
        $emailData = [
            'id' => 'email_'.uniqid(),
            'to' => $userData['email'] ?? 'unknown@example.com',
            'subject' => $config['subject'] ?? 'Welcome!',
            'template' => $config['template'] ?? 'welcome',
            'sent_at' => date('Y-m-d H:i:s'),
        ];

        return ActionResult::success([
            'email_id' => $emailData['id'],
            'email_sent' => true,
            'email_data' => $emailData,
        ]);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        $userData = $context->getData('user') ?? [];

        return ! empty($userData['email']);
    }

    public function getName(): string
    {
        return 'Send Welcome Email';
    }

    public function getDescription(): string
    {
        return 'Sends a welcome email to the user';
    }
}
