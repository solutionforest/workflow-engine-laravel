<?php

namespace SolutionForest\WorkflowMastery\Core;

use SolutionForest\WorkflowMastery\Contracts\WorkflowAction;

/**
 * Fluent workflow builder for simplified workflow creation
 * 
 * @example
 * $workflow = WorkflowBuilder::create('user-onboarding')
 *     ->description('Complete user onboarding process')
 *     ->addStep('send_welcome', SendWelcomeEmailAction::class)
 *     ->addStep('create_profile', CreateUserProfileAction::class)
 *     ->when('user.premium', function($builder) {
 *         $builder->addStep('setup_premium', SetupPremiumFeaturesAction::class);
 *     })
 *     ->build();
 */
class WorkflowBuilder
{
    private string $name;
    private string $version = '1.0';
    private string $description = '';
    private array $steps = [];
    private array $transitions = [];
    private array $metadata = [];
    private array $conditionalSteps = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Create a new workflow builder
     */
    public static function create(string $name): self
    {
        return new self($name);
    }

    /**
     * Set workflow description
     */
    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set workflow version
     */
    public function version(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Add a workflow step
     */
    public function addStep(
        string $id,
        string|WorkflowAction $action,
        array $config = [],
        ?int $timeout = null,
        int $retryAttempts = 0
    ): self {
        $this->steps[] = [
            'id' => $id,
            'action' => is_string($action) ? $action : $action::class,
            'config' => $config,
            'timeout' => $timeout,
            'retry_attempts' => $retryAttempts,
        ];
        return $this;
    }

    /**
     * Add the first step (syntactic sugar)
     */
    public function startWith(
        string|WorkflowAction $action,
        array $config = [],
        ?int $timeout = null,
        int $retryAttempts = 0
    ): self {
        $stepId = 'step_' . (count($this->steps) + 1);
        return $this->addStep($stepId, $action, $config, $timeout, $retryAttempts);
    }

    /**
     * Add a sequential step (syntactic sugar)
     */
    public function then(
        string|WorkflowAction $action,
        array $config = [],
        ?int $timeout = null,
        int $retryAttempts = 0
    ): self {
        $stepId = 'step_' . (count($this->steps) + 1);
        return $this->addStep($stepId, $action, $config, $timeout, $retryAttempts);
    }

    /**
     * Add conditional step
     */
    public function when(string $condition, callable $callback): self
    {
        $originalStepsCount = count($this->steps);
        $callback($this);
        $newStepsCount = count($this->steps);
        
        // Mark new steps as conditional
        for ($i = $originalStepsCount; $i < $newStepsCount; $i++) {
            $this->steps[$i]['condition'] = $condition;
        }
        
        return $this;
    }

    /**
     * Add email step (common pattern)
     */
    public function email(
        string $template,
        string $to,
        string $subject,
        array $data = []
    ): self {
        return $this->addStep(
            'email_' . count($this->steps),
            'SolutionForest\\WorkflowMastery\\Actions\\EmailAction',
            [
                'template' => $template,
                'to' => $to,
                'subject' => $subject,
                'data' => $data,
            ]
        );
    }

    /**
     * Add delay step (common pattern)
     */
    public function delay(int $seconds = null, int $minutes = null, int $hours = null): self
    {
        $totalSeconds = $seconds ?? 0;
        $totalSeconds += ($minutes ?? 0) * 60;
        $totalSeconds += ($hours ?? 0) * 3600;

        return $this->addStep(
            'delay_' . count($this->steps),
            'SolutionForest\\WorkflowMastery\\Actions\\DelayAction',
            ['seconds' => $totalSeconds]
        );
    }

    /**
     * Add HTTP request step (common pattern)
     */
    public function http(
        string $url,
        string $method = 'GET',
        array $data = [],
        array $headers = []
    ): self {
        return $this->addStep(
            'http_' . count($this->steps),
            'SolutionForest\\WorkflowMastery\\Actions\\HttpAction',
            [
                'url' => $url,
                'method' => $method,
                'data' => $data,
                'headers' => $headers,
            ]
        );
    }

    /**
     * Add condition check
     */
    public function condition(string $condition): self
    {
        return $this->addStep(
            'condition_' . count($this->steps),
            'SolutionForest\\WorkflowMastery\\Actions\\ConditionAction',
            ['condition' => $condition]
        );
    }

    /**
     * Add metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Build the workflow definition
     */
    public function build(): WorkflowDefinition
    {
        // Convert builder format to Step objects
        $steps = [];
        foreach ($this->steps as $stepData) {
            $steps[] = new Step(
                id: $stepData['id'],
                actionClass: $stepData['action'],
                config: $stepData['config'],
                timeout: $stepData['timeout'] ? (string) $stepData['timeout'] : null,
                retryAttempts: $stepData['retry_attempts'],
                conditions: isset($stepData['condition']) ? [$stepData['condition']] : []
            );
        }

        // Add description to metadata
        if ($this->description) {
            $this->metadata['description'] = $this->description;
        }

        return new WorkflowDefinition(
            name: $this->name,
            version: $this->version,
            steps: $steps,
            transitions: $this->transitions,
            metadata: $this->metadata
        );
    }

    /**
     * Quick workflow for common patterns
     */
    public static function quick(): QuickWorkflowBuilder
    {
        return new QuickWorkflowBuilder();
    }
}

/**
 * Quick workflow builder for common patterns
 */
class QuickWorkflowBuilder
{
    /**
     * User onboarding workflow
     */
    public function userOnboarding(string $name = 'user-onboarding'): WorkflowBuilder
    {
        return WorkflowBuilder::create($name)
            ->description('Standard user onboarding process')
            ->email(
                template: 'welcome',
                to: '{{ user.email }}',
                subject: 'Welcome to {{ app.name }}!'
            )
            ->delay(minutes: 5)
            ->addStep('create_profile', 'App\\Actions\\CreateUserProfileAction')
            ->addStep('assign_role', 'App\\Actions\\AssignDefaultRoleAction');
    }

    /**
     * Order processing workflow
     */
    public function orderProcessing(string $name = 'order-processing'): WorkflowBuilder
    {
        return WorkflowBuilder::create($name)
            ->description('E-commerce order processing workflow')
            ->addStep('validate_order', 'App\\Actions\\ValidateOrderAction')
            ->addStep('charge_payment', 'App\\Actions\\ChargePaymentAction')
            ->addStep('update_inventory', 'App\\Actions\\UpdateInventoryAction')
            ->email(
                template: 'order-confirmation',
                to: '{{ order.customer.email }}',
                subject: 'Order Confirmation #{{ order.id }}'
            );
    }

    /**
     * Document approval workflow
     */
    public function documentApproval(string $name = 'document-approval'): WorkflowBuilder
    {
        return WorkflowBuilder::create($name)
            ->description('Document approval process')
            ->addStep('submit_document', 'App\\Actions\\SubmitDocumentAction')
            ->addStep('assign_reviewer', 'App\\Actions\\AssignReviewerAction')
            ->email(
                template: 'review-request',
                to: '{{ reviewer.email }}',
                subject: 'Document Review Request'
            )
            ->addStep('review_document', 'App\\Actions\\ReviewDocumentAction')
            ->when('review.approved', function($builder) {
                $builder->addStep('approve_document', 'App\\Actions\\ApproveDocumentAction');
            })
            ->when('review.rejected', function($builder) {
                $builder->addStep('reject_document', 'App\\Actions\\RejectDocumentAction');
            });
    }
}
