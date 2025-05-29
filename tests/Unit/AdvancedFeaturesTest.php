<?php

use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\CreateUserProfileAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\RiskyAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\SendWelcomeEmailAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\VerifyIdentityAction;

describe('Advanced Features Tests', function () {

    beforeEach(function () {
        $this->engine = app(WorkflowEngine::class);
    });

    test('email action configuration matches documentation examples', function () {
        $workflow = WorkflowBuilder::create('email-test')
            ->email(
                'welcome-email',
                '{{ user.email }}',
                'Welcome to {{ app.name }}!',
                ['user_name' => '{{ user.name }}']
            )
            ->build();

        $steps = $workflow->getSteps();
        $step = $steps['email_0'];
        expect($step->getConfig())->toBe([
            'template' => 'welcome-email',
            'to' => '{{ user.email }}',
            'subject' => 'Welcome to {{ app.name }}!',
            'data' => ['user_name' => '{{ user.name }}'],
        ]);
    });

    test('delay action supports all time units from documentation', function () {
        $workflow = WorkflowBuilder::create('delay-test')
            ->delay(seconds: 30)           // 30 second delay
            ->delay(minutes: 5)            // 5 minute delay
            ->delay(hours: 1, minutes: 30) // 1.5 hour delay
            ->build();

        expect($workflow->getSteps())->toHaveCount(3);

        $steps = $workflow->getSteps();
        expect($steps['delay_0']->getConfig()['seconds'])->toBe(30);
        expect($steps['delay_1']->getConfig()['seconds'])->toBe(300);
        expect($steps['delay_2']->getConfig()['seconds'])->toBe(5400); // 1.5 hours
    });

    test('http action supports all documented parameters', function () {
        $workflow = WorkflowBuilder::create('http-test')
            ->http(
                'https://api.example.com/users',
                'POST',
                ['name' => '{{ user.name }}', 'email' => '{{ user.email }}'],
                ['Authorization' => 'Bearer {{ api.token }}']
            )
            ->build();

        $steps = $workflow->getSteps();
        $stepId = array_keys($steps)[0]; // Get first step ID
        $step = $steps[$stepId];
        expect($step->getConfig())->toBe([
            'url' => 'https://api.example.com/users',
            'method' => 'POST',
            'data' => ['name' => '{{ user.name }}', 'email' => '{{ user.email }}'],
            'headers' => ['Authorization' => 'Bearer {{ api.token }}'],
        ]);
    });

    test('conditional workflows work as documented', function () {
        $workflow = WorkflowBuilder::create('conditional-test')
            ->addStep('validate_order', CreateUserProfileAction::class)
            ->when('user.age >= 18', function ($builder) {
                $builder->addStep('verify_identity', VerifyIdentityAction::class);
                $builder->addStep('premium_processing', SendWelcomeEmailAction::class);
            })
            ->addStep('finalize_order', CreateUserProfileAction::class)
            ->build();

        expect($workflow->getSteps())->toHaveCount(4);

        // Check that conditional steps have the condition by step ID
        $steps = $workflow->getSteps();
        $conditionalStep1 = $steps['verify_identity'];
        $conditionalStep2 = $steps['premium_processing'];

        expect($conditionalStep1->getConditions())->toBe(['user.age >= 18']);
        expect($conditionalStep2->getConditions())->toBe(['user.age >= 18']);

        // Non-conditional steps should not have condition
        expect($steps['validate_order']->getConditions())->toBe([]);
        expect($steps['finalize_order']->getConditions())->toBe([]);
    });

    test('workflow builder fluent interface supports method chaining', function () {
        $workflow = WorkflowBuilder::create('chaining-test')
            ->description('Test fluent interface')
            ->version('1.5')
            ->startWith(CreateUserProfileAction::class, ['profile_type' => 'basic'])
            ->then(SendWelcomeEmailAction::class)
            ->email('tips-email', '{{ user.email }}', 'Getting Started Tips')
            ->delay(minutes: 5)
            ->when('user.premium = true', function ($builder) {
                $builder->then(VerifyIdentityAction::class);
            })
            ->http('https://api.example.com/track', 'POST', ['user_id' => '{{ user.id }}'])
            ->build();

        expect($workflow->getName())->toBe('chaining-test');
        expect($workflow->getSteps())->toHaveCount(6);

        // Verify the chain of actions
        $stepClasses = array_map(fn ($step) => $step->getActionClass(), $workflow->getSteps());
        expect($stepClasses)->toContain(CreateUserProfileAction::class);
        expect($stepClasses)->toContain(SendWelcomeEmailAction::class);
        expect($stepClasses)->toContain('SolutionForest\\WorkflowEngine\\Actions\\EmailAction');
        expect($stepClasses)->toContain('SolutionForest\\WorkflowEngine\\Actions\\DelayAction');
        expect($stepClasses)->toContain(VerifyIdentityAction::class);
        expect($stepClasses)->toContain('SolutionForest\\WorkflowEngine\\Actions\\HttpAction');
    });

    test('workflow with timeout and retry configuration works', function () {
        $workflow = WorkflowBuilder::create('robust-test')
            ->addStep('reliable_action', CreateUserProfileAction::class)
            ->addStep('risky_action', RiskyAction::class, ['failure_rate' => 0.8], 60, 5)
            ->addStep('final_action', SendWelcomeEmailAction::class, [], 30, 2)
            ->build();

        expect($workflow->getSteps())->toHaveCount(3);

        $steps = $workflow->getSteps();
        $reliableStep = $steps['reliable_action'];
        expect($reliableStep->getTimeout())->toBeNull();
        expect($reliableStep->getRetryAttempts())->toBe(0);

        $riskyStep = $steps['risky_action'];
        expect($riskyStep->getTimeout())->toBe('60');
        expect($riskyStep->getRetryAttempts())->toBe(5);
        expect($riskyStep->getConfig()['failure_rate'])->toBe(0.8);

        $finalStep = $steps['final_action'];
        expect($finalStep->getTimeout())->toBe('30');
        expect($finalStep->getRetryAttempts())->toBe(2);
    });

    test('complex workflow execution with all features', function () {
        $workflow = WorkflowBuilder::create('integration-test')
            ->startWith(CreateUserProfileAction::class, ['profile_type' => 'premium'])
            ->email('welcome-email', '{{ user.email }}', 'Welcome Premium User!')
            ->delay(seconds: 1) // Short delay for testing
            ->when('user.age >= 21', function ($builder) {
                $builder->addStep('age_verification', VerifyIdentityAction::class, [], '30s', 2);
            })
            ->http('https://httpbin.org/post', 'POST', ['user_id' => '{{ user.id }}'])
            ->then(SendWelcomeEmailAction::class, ['template' => 'completion'])
            ->build();

        // Convert to engine format and execute
        $definition = $workflow->toArray();
        $workflowId = $this->engine->start('integration-test', $definition, [
            'user' => [
                'id' => 123,
                'email' => 'test@example.com',
                'age' => 25,
                'profile_type' => 'premium',
            ],
        ]);

        expect($workflowId)->not->toBeEmpty();

        $instance = $this->engine->getInstance($workflowId);
        expect($instance)->not->toBeNull();
        expect($instance->getContext()->getData('user')['id'])->toBe(123);
    });

    test('pre-built workflow patterns work correctly', function () {
        // Test the userOnboarding pattern
        $userWorkflow = WorkflowBuilder::quick()->userOnboarding('custom-onboarding');
        expect($userWorkflow)->toBeInstanceOf(WorkflowBuilder::class);

        $builtWorkflow = $userWorkflow->build();
        expect($builtWorkflow->getName())->toBe('custom-onboarding');
        expect($builtWorkflow->getSteps())->not->toBeEmpty();

        // Test the orderProcessing pattern
        $orderWorkflow = WorkflowBuilder::quick()->orderProcessing('custom-order');
        expect($orderWorkflow)->toBeInstanceOf(WorkflowBuilder::class);

        $builtOrderWorkflow = $orderWorkflow->build();
        expect($builtOrderWorkflow->getName())->toBe('custom-order');
        expect($builtOrderWorkflow->getSteps())->not->toBeEmpty();

        // Test the documentApproval pattern
        $docWorkflow = WorkflowBuilder::quick()->documentApproval('custom-approval');
        expect($docWorkflow)->toBeInstanceOf(WorkflowBuilder::class);

        $builtDocWorkflow = $docWorkflow->build();
        expect($builtDocWorkflow->getName())->toBe('custom-approval');
        expect($builtDocWorkflow->getSteps())->not->toBeEmpty();
    });

    test('workflow validation catches invalid configurations', function () {
        expect(function () {
            WorkflowBuilder::create(''); // Empty name should fail
        })->toThrow(\SolutionForest\WorkflowEngine\Exceptions\InvalidWorkflowDefinitionException::class);

        expect(function () {
            WorkflowBuilder::create('123invalid'); // Invalid name format
        })->toThrow(\SolutionForest\WorkflowEngine\Exceptions\InvalidWorkflowDefinitionException::class);

        expect(function () {
            WorkflowBuilder::create('valid-name')
                ->delay(); // No delay specified should fail
        })->toThrow(\SolutionForest\WorkflowEngine\Exceptions\InvalidWorkflowDefinitionException::class);

        expect(function () {
            WorkflowBuilder::create('valid-name')
                ->when('', function ($builder) {}); // Empty condition should fail
        })->toThrow(\SolutionForest\WorkflowEngine\Exceptions\InvalidWorkflowDefinitionException::class);
    });

});
