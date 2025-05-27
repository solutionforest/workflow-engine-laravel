<?php

namespace SolutionForest\WorkflowMastery\Tests\Integration;

use SolutionForest\WorkflowMastery\Core\WorkflowState;
use SolutionForest\WorkflowMastery\Tests\TestCase;

class WorkflowIntegrationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_execute_a_complete_workflow(): void
    {
        // Create a more complex workflow with multiple steps
        $definition = [
            'name' => 'User Onboarding Workflow',
            'version' => '1.0',
            'steps' => [
                [
                    'id' => 'welcome',
                    'name' => 'Welcome User',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Welcome {{name}} to our platform!',
                        'level' => 'info',
                    ],
                ],
                [
                    'id' => 'setup_profile',
                    'name' => 'Setup User Profile',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Setting up profile for {{name}} with email {{email}}',
                        'level' => 'info',
                    ],
                ],
                [
                    'id' => 'send_confirmation',
                    'name' => 'Send Confirmation',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Sending confirmation email to {{email}}',
                        'level' => 'info',
                    ],
                ],
            ],
            'transitions' => [
                [
                    'from' => 'welcome',
                    'to' => 'setup_profile',
                ],
                [
                    'from' => 'setup_profile',
                    'to' => 'send_confirmation',
                ],
            ],
        ];

        $context = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'userId' => 123,
        ];

        // Start the workflow using helper function
        $workflowId = start_workflow('user-onboarding-123', $definition, $context);

        // Verify workflow was created and completed
        $this->assertNotEmpty($workflowId);
        $this->assertEquals('user-onboarding-123', $workflowId);

        // Get workflow instance using helper
        $instance = get_workflow($workflowId);

        // Verify the workflow completed successfully
        $this->assertEquals(WorkflowState::COMPLETED, $instance->getState());
        $this->assertEquals('User Onboarding Workflow', $instance->getName());

        // Verify the context contains original data plus step outputs
        $workflowData = $instance->getContext()->getData();
        $this->assertEquals('John Doe', $workflowData['name']);
        $this->assertEquals('john@example.com', $workflowData['email']);
        $this->assertEquals(123, $workflowData['userId']);

        // Verify that all steps completed successfully
        $this->assertCount(3, $instance->getCompletedSteps());
        $this->assertContains('welcome', $instance->getCompletedSteps());
        $this->assertContains('setup_profile', $instance->getCompletedSteps());
        $this->assertContains('send_confirmation', $instance->getCompletedSteps());

        // Verify no failed steps
        $this->assertEmpty($instance->getFailedSteps());

        // Check workflow status
        $status = workflow()->getStatus($workflowId);
        $this->assertEquals('completed', $status['state']);
        $this->assertEquals('User Onboarding Workflow', $status['name']);
        $this->assertEquals(100, $status['progress']); // 100% complete
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_handle_workflow_cancellation(): void
    {
        $definition = [
            'name' => 'Cancellable Workflow',
            'steps' => [
                [
                    'id' => 'step1',
                    'name' => 'First Step',
                    'action' => 'log',
                    'parameters' => ['message' => 'Starting process'],
                ],
            ],
        ];

        // Start workflow
        $workflowId = start_workflow('cancellable-workflow', $definition);

        // Cancel workflow
        cancel_workflow($workflowId, 'User requested cancellation');

        // Verify cancellation
        $instance = get_workflow($workflowId);
        $this->assertEquals(WorkflowState::CANCELLED, $instance->getState());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_list_and_filter_workflows(): void
    {
        $definition1 = [
            'name' => 'Workflow 1',
            'steps' => [
                ['id' => 'step1', 'action' => 'log', 'parameters' => ['message' => 'Test']],
            ],
        ];

        $definition2 = [
            'name' => 'Workflow 2',
            'steps' => [
                ['id' => 'step1', 'action' => 'log', 'parameters' => ['message' => 'Test']],
            ],
        ];

        // Start two workflows
        $workflow1Id = start_workflow('list-test-1', $definition1);
        $workflow2Id = start_workflow('list-test-2', $definition2);

        // Cancel one
        cancel_workflow($workflow2Id);

        // List all workflows
        $allWorkflows = workflow()->listWorkflows();
        $this->assertGreaterThanOrEqual(2, count($allWorkflows));

        // Filter by state
        $completedWorkflows = workflow()->listWorkflows(['state' => WorkflowState::COMPLETED]);
        $cancelledWorkflows = workflow()->listWorkflows(['state' => WorkflowState::CANCELLED]);

        $this->assertGreaterThanOrEqual(1, count($completedWorkflows));
        $this->assertGreaterThanOrEqual(1, count($cancelledWorkflows));

        // Verify specific workflows exist in filtered results
        $completedIds = array_column($completedWorkflows, 'workflow_id');
        $cancelledIds = array_column($cancelledWorkflows, 'workflow_id');

        $this->assertContains($workflow1Id, $completedIds);
        $this->assertContains($workflow2Id, $cancelledIds);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_execute_conditional_workflows(): void
    {
        $definition = [
            'name' => 'Conditional Approval Workflow',
            'version' => '1.0',
            'steps' => [
                [
                    'id' => 'validate_request',
                    'name' => 'Validate Request',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Validating request from {{user}}',
                        'level' => 'info',
                    ],
                ],
                [
                    'id' => 'auto_approve',
                    'name' => 'Auto Approve',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Auto-approving request for premium user {{user}}',
                        'level' => 'info',
                    ],
                ],
                [
                    'id' => 'manual_review',
                    'name' => 'Manual Review Required',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Manual review required for user {{user}}',
                        'level' => 'warning',
                    ],
                ],
                [
                    'id' => 'notify_completion',
                    'name' => 'Notify Completion',
                    'action' => 'log',
                    'parameters' => [
                        'message' => 'Process completed for {{user}}',
                        'level' => 'info',
                    ],
                ],
            ],
            'transitions' => [
                [
                    'from' => 'validate_request',
                    'to' => 'auto_approve',
                    'condition' => 'tier === premium',
                ],
                [
                    'from' => 'validate_request',
                    'to' => 'manual_review',
                    'condition' => 'tier !== premium',
                ],
                [
                    'from' => 'auto_approve',
                    'to' => 'notify_completion',
                ],
                [
                    'from' => 'manual_review',
                    'to' => 'notify_completion',
                ],
            ],
        ];

        // Test premium user path (should auto-approve)
        $premiumContext = [
            'user' => 'Alice Premium',
            'tier' => 'premium',
            'amount' => 1000,
        ];

        $premiumWorkflowId = start_workflow('premium-approval-123', $definition, $premiumContext);
        $premiumInstance = get_workflow($premiumWorkflowId);

        // Verify premium workflow took auto-approval path
        $this->assertEquals(WorkflowState::COMPLETED, $premiumInstance->getState());
        $this->assertContains('validate_request', $premiumInstance->getCompletedSteps());
        $this->assertContains('auto_approve', $premiumInstance->getCompletedSteps());
        $this->assertContains('notify_completion', $premiumInstance->getCompletedSteps());
        $this->assertNotContains('manual_review', $premiumInstance->getCompletedSteps());

        // Test regular user path (should require manual review)
        $regularContext = [
            'user' => 'Bob Regular',
            'tier' => 'basic',
            'amount' => 5000,
        ];

        $regularWorkflowId = start_workflow('regular-approval-456', $definition, $regularContext);
        $regularInstance = get_workflow($regularWorkflowId);

        // Verify regular workflow took manual review path
        $this->assertEquals(WorkflowState::COMPLETED, $regularInstance->getState());
        $this->assertContains('validate_request', $regularInstance->getCompletedSteps());
        $this->assertContains('manual_review', $regularInstance->getCompletedSteps());
        $this->assertContains('notify_completion', $regularInstance->getCompletedSteps());
        $this->assertNotContains('auto_approve', $regularInstance->getCompletedSteps());
    }
}
