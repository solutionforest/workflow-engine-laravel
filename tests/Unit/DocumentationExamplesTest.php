<?php

use SolutionForest\WorkflowEngine\Core\WorkflowBuilder;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Core\WorkflowState;
use SolutionForest\WorkflowEngine\Laravel\Tests\Actions\ECommerce\ProcessPaymentAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\CreateUserProfileAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\RiskyAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\SendWelcomeEmailAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\VerifyIdentityAction;

describe('Documentation Examples', function () {

    beforeEach(function () {
        $this->engine = app(WorkflowEngine::class);
    });

    test('getting started - basic workflow creation works', function () {
        $registrationWorkflow = WorkflowBuilder::create('user-registration')
            ->addStep('create-profile', CreateUserProfileAction::class)
            ->email('welcome-email', '{{ user.email }}', 'Welcome!')
            ->delay(hours: 24)
            ->email('tips-email', '{{ user.email }}', 'Getting Started Tips')
            ->build();

        expect($registrationWorkflow)->not->toBeNull();
        expect($registrationWorkflow->getName())->toBe('user-registration');
        expect($registrationWorkflow->getSteps())->toHaveCount(4);
    });

    test('getting started - workflow execution works', function () {
        $registrationWorkflow = WorkflowBuilder::create('user-registration')
            ->addStep('create-profile', CreateUserProfileAction::class)
            ->build();

        $user = ['id' => 1, 'email' => 'test@example.com', 'name' => 'John Doe'];
        $registrationData = ['source' => 'web'];

        // Convert WorkflowDefinition to array format for engine
        $definition = $registrationWorkflow->toArray();
        $workflowId = $this->engine->start('user-registration', $definition, [
            'user' => $user,
            'registration_data' => $registrationData,
        ]);

        expect($workflowId)->not->toBeEmpty();

        $instance = $this->engine->getInstance($workflowId);
        $context = $instance->getContext();

        // Direct test of the getData method
        $contextClass = get_class($context);
        expect($contextClass)->toBe(\SolutionForest\WorkflowEngine\Core\WorkflowContext::class);

        // Test the getData method implementation directly
        $allData = $context->getData();
        expect($allData)->toHaveKey('user');
        expect($allData)->toHaveKey('registration_data');

        $userData = $context->getData('user');
        $registrationData = $context->getData('registration_data');

        expect($userData)->toBe($user);
        expect($registrationData)->toBe($registrationData);

        // Check that the action added new data
        expect($context->hasData('profile_id'))->toBe(true);
        expect($context->hasData('profile'))->toBe(true);
    });

    test('getting started - workflow states work correctly', function () {
        $workflow = WorkflowBuilder::create('test-workflow')
            ->addStep('test-action', CreateUserProfileAction::class)
            ->build();

        $definition = $workflow->toArray();
        $workflowId = $this->engine->start('test-workflow', $definition, ['test' => 'data']);
        $instance = $this->engine->getInstance($workflowId);
        $state = $instance->getState();

        expect($state)->toBeInstanceOf(WorkflowState::class);
        expect(in_array($state->value, ['running', 'completed', 'failed', 'paused']))->toBeTrue();
        expect($state->label())->not->toBeEmpty();
        expect($state->color())->not->toBeEmpty();
        expect($state->icon())->not->toBeEmpty();
    });

    test('getting started - error handling with timeout and retry works', function () {
        $workflow = WorkflowBuilder::create('robust-workflow')
            ->addStep('risky-operation', RiskyAction::class, [], '30s', 3) // timeout: 30s, retry: 3 attempts
            ->build();

        expect($workflow)->not->toBeNull();

        // Debug: check steps count and details
        $steps = $workflow->getSteps();
        expect($steps)->toHaveCount(1);

        $step = $steps['risky-operation'];
        expect($step->getTimeout())->toBe('30s'); // Should remain as string format
        expect($step->getRetryAttempts())->toBe(3);
    });

    test('api reference - addStep method works', function () {
        $builder = WorkflowBuilder::create('payment-workflow');

        // Basic step
        $builder->addStep('process-payment', ProcessPaymentAction::class);

        // Step with config, timeout, and retry
        $builder->addStep('process-payment-complex', ProcessPaymentAction::class, ['currency' => 'USD'], '30s', 3);

        $workflow = $builder->build();

        expect($workflow->getSteps())->toHaveCount(2);

        // Access steps by ID instead of numeric index
        $steps = $workflow->getSteps();

        $basicStep = $steps['process-payment'];
        expect($basicStep->getId())->toBe('process-payment');
        expect($basicStep->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Actions\\ECommerce\\ProcessPaymentAction');

        $complexStep = $steps['process-payment-complex'];
        expect($complexStep->getId())->toBe('process-payment-complex');
        expect($complexStep->getConfig()['currency'])->toBe('USD');
    });

    test('api reference - email method works', function () {
        $workflow = WorkflowBuilder::create('email-workflow')
            ->email('welcome-email', '{{ user.email }}', 'Welcome {{ user.name }}!', ['welcome_bonus' => 100])
            ->build();

        expect($workflow->getSteps())->toHaveCount(1);

        $steps = $workflow->getSteps();
        $step = $steps['email_0'];
        expect($step->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Actions\\EmailAction');
        expect($step->getConfig()['template'])->toBe('welcome-email');
        expect($step->getConfig()['to'])->toBe('{{ user.email }}');
        expect($step->getConfig()['subject'])->toBe('Welcome {{ user.name }}!');
        expect($step->getConfig()['data'])->toBe(['welcome_bonus' => 100]);
    });

    test('api reference - http method works', function () {
        $workflow = WorkflowBuilder::create('http-workflow')
            ->http('https://api.example.com/webhooks', 'POST', [
                'event' => 'user_registered',
                'user_id' => '{{ user.id }}',
            ])
            ->build();

        expect($workflow->getSteps())->toHaveCount(1);

        $steps = $workflow->getSteps();
        $step = $steps['http_0'];
        expect($step->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Actions\\HttpAction');
        expect($step->getConfig()['url'])->toBe('https://api.example.com/webhooks');
        expect($step->getConfig()['method'])->toBe('POST');
        expect($step->getConfig()['data'])->toBe([
            'event' => 'user_registered',
            'user_id' => '{{ user.id }}',
        ]);
    });

    test('api reference - delay method works', function () {
        $workflow = WorkflowBuilder::create('delay-workflow')
            ->delay(minutes: 30)
            ->delay(hours: 2)
            ->delay(hours: 24) // 1 day equivalent
            ->build();

        expect($workflow->getSteps())->toHaveCount(3);

        $steps = $workflow->getSteps();

        // 30 minutes = 1800 seconds
        expect($steps['delay_0']->getConfig()['seconds'])->toBe(1800);

        // 2 hours = 7200 seconds
        expect($steps['delay_1']->getConfig()['seconds'])->toBe(7200);

        // 24 hours = 86400 seconds
        expect($steps['delay_2']->getConfig()['seconds'])->toBe(86400);
    });

    test('api reference - when conditional method works', function () {
        $workflow = WorkflowBuilder::create('conditional-workflow')
            ->addStep('validate-user', CreateUserProfileAction::class)
            ->when('user.age >= 18', function ($builder) {
                $builder->addStep('verify-identity', VerifyIdentityAction::class);
            })
            ->build();

        expect($workflow->getSteps())->toHaveCount(2);

        $steps = $workflow->getSteps();
        $conditionalStep = $steps['verify-identity'];
        expect($conditionalStep->getId())->toBe('verify-identity');
        expect($conditionalStep->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Support\\TestActions\\VerifyIdentityAction');
        expect($conditionalStep->getConditions())->toBe(['user.age >= 18']);
    });

    test('api reference - startWith method works', function () {
        $workflow = WorkflowBuilder::create('start-workflow')
            ->startWith(CreateUserProfileAction::class, ['strict' => true])
            ->build();

        expect($workflow->getSteps())->toHaveCount(1);

        $steps = $workflow->getSteps();
        $step = $steps['step_1'];
        expect($step->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Support\\TestActions\\CreateUserProfileAction');
        expect($step->getConfig()['strict'])->toBe(true);
    });

    test('api reference - then method works', function () {
        $workflow = WorkflowBuilder::create('chain-workflow')
            ->startWith(CreateUserProfileAction::class)
            ->then(SendWelcomeEmailAction::class)
            ->then(VerifyIdentityAction::class)
            ->build();

        expect($workflow->getSteps())->toHaveCount(3);

        $steps = $workflow->getSteps();
        expect($steps['step_1']->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Support\\TestActions\\CreateUserProfileAction');
        expect($steps['step_2']->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Support\\TestActions\\SendWelcomeEmailAction');
        expect($steps['step_3']->getActionClass())->toBe('SolutionForest\\WorkflowEngine\\Laravel\\Tests\\Support\\TestActions\\VerifyIdentityAction');
    });

    test('complex workflow combining multiple features works', function () {
        $workflow = WorkflowBuilder::create('complex-workflow')
            ->description('A complex workflow showcasing all features')
            ->version('2.0')
            ->startWith(CreateUserProfileAction::class, ['profile_type' => 'premium'])
            ->email('welcome-email', '{{ user.email }}', 'Welcome to Premium!')
            ->when('user.age >= 21', function ($builder) {
                $builder->addStep('age-verification', VerifyIdentityAction::class, [], '60s', 2);
            })
            ->delay(minutes: 5)
            ->http('https://api.example.com/notify', 'POST', ['user_id' => '{{ user.id }}'])
            ->then(ProcessPaymentAction::class, ['amount' => 99.99], '120s', 3)
            ->build();

        expect($workflow->getName())->toBe('complex-workflow');
        expect($workflow->getSteps())->toHaveCount(6); // startWith + email + conditional + delay + http + then

        // Test execution
        $definition = $workflow->toArray();
        $workflowId = $this->engine->start('complex-workflow', $definition, [
            'user' => ['id' => 1, 'email' => 'test@example.com', 'age' => 25],
        ]);

        expect($workflowId)->not->toBeEmpty();
        $instance = $this->engine->getInstance($workflowId);
        expect($instance)->not->toBeNull();
    });

});
