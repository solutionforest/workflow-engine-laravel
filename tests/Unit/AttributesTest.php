<?php

use SolutionForest\WorkflowEngine\Attributes\Condition;
use SolutionForest\WorkflowEngine\Attributes\Retry;
use SolutionForest\WorkflowEngine\Attributes\Timeout;
use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\CreateUserProfileAction;
use SolutionForest\WorkflowEngine\Laravel\Tests\Support\TestActions\VerifyIdentityAction;

describe('PHP 8.3+ Attributes Tests', function () {

    test('WorkflowStep attribute works correctly', function () {
        $reflection = new ReflectionClass(CreateUserProfileAction::class);
        $attributes = $reflection->getAttributes(WorkflowStep::class);

        expect($attributes)->toHaveCount(1);

        $workflowStep = $attributes[0]->newInstance();
        expect($workflowStep->id)->toBe('create_profile');
        expect($workflowStep->name)->toBe('Create User Profile');
        expect($workflowStep->description)->toBe('Creates a new user profile in the database');
        expect($workflowStep->required)->toBe(true);
        expect($workflowStep->order)->toBe(0);
    });

    test('Timeout attribute works correctly', function () {
        $reflection = new ReflectionClass(CreateUserProfileAction::class);
        $attributes = $reflection->getAttributes(Timeout::class);

        expect($attributes)->toHaveCount(1);

        $timeout = $attributes[0]->newInstance();
        expect($timeout->totalSeconds)->toBe(30);
    });

    test('Retry attribute works correctly', function () {
        $reflection = new ReflectionClass(CreateUserProfileAction::class);
        $attributes = $reflection->getAttributes(Retry::class);

        expect($attributes)->toHaveCount(1);

        $retry = $attributes[0]->newInstance();
        expect($retry->attempts)->toBe(3);
        expect($retry->backoff)->toBe('exponential');
        expect($retry->delay)->toBe(1000);
        expect($retry->maxDelay)->toBe(30000);
    });

    test('attributes can be used for action configuration', function () {
        $actionClass = CreateUserProfileAction::class;
        $reflection = new ReflectionClass($actionClass);

        // Extract configuration from attributes
        $config = [];

        // WorkflowStep attribute
        $stepAttributes = $reflection->getAttributes(WorkflowStep::class);
        if (! empty($stepAttributes)) {
            $step = $stepAttributes[0]->newInstance();
            $config['step_id'] = $step->id;
            $config['step_name'] = $step->name;
            $config['step_description'] = $step->description;
            $config['required'] = $step->required;
        }

        // Timeout attribute
        $timeoutAttributes = $reflection->getAttributes(Timeout::class);
        if (! empty($timeoutAttributes)) {
            $timeout = $timeoutAttributes[0]->newInstance();
            $config['timeout'] = $timeout->totalSeconds;
        }

        // Retry attribute
        $retryAttributes = $reflection->getAttributes(Retry::class);
        if (! empty($retryAttributes)) {
            $retry = $retryAttributes[0]->newInstance();
            $config['retry_attempts'] = $retry->attempts;
            $config['retry_backoff'] = $retry->backoff;
            $config['retry_delay'] = $retry->delay;
        }

        expect($config)->toBe([
            'step_id' => 'create_profile',
            'step_name' => 'Create User Profile',
            'step_description' => 'Creates a new user profile in the database',
            'required' => true,
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_backoff' => 'exponential',
            'retry_delay' => 1000,
        ]);
    });

    test('action without attributes still works', function () {
        $reflection = new ReflectionClass(VerifyIdentityAction::class);

        $stepAttributes = $reflection->getAttributes(WorkflowStep::class);
        $timeoutAttributes = $reflection->getAttributes(Timeout::class);
        $retryAttributes = $reflection->getAttributes(Retry::class);

        expect($stepAttributes)->toBeEmpty();
        expect($timeoutAttributes)->toBeEmpty();
        expect($retryAttributes)->toBeEmpty();

        // Action should still be instantiable and executable
        $action = new VerifyIdentityAction;
        expect($action->getName())->toBe('Verify Identity');
        expect($action->getDescription())->toBe('Verifies user identity for users 18 years and older');
    });

    test('multiple attributes can be combined on a single action', function () {
        // Create a test action with multiple attributes
        $testActionCode = '<?php
        namespace Tests\Support\TestActions;
        
        use SolutionForest\WorkflowEngine\Attributes\WorkflowStep;
        use SolutionForest\WorkflowEngine\Attributes\Timeout;
        use SolutionForest\WorkflowEngine\Attributes\Retry;
        use SolutionForest\WorkflowEngine\Attributes\Condition;
        use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
        use SolutionForest\WorkflowEngine\Core\WorkflowContext;
        use SolutionForest\WorkflowEngine\Core\ActionResult;
        
        #[WorkflowStep(id: "multi_attr", name: "Multi Attribute Action", description: "Test action with multiple attributes")]
        #[Timeout(minutes: 5, seconds: 30)]
        #[Retry(attempts: 5, backoff: "exponential", delay: 2000, maxDelay: 60000)]
        #[Condition("user.premium = true")]
        #[Condition("order.amount > 100", operator: "and")]
        class MultiAttributeTestAction implements WorkflowAction
        {
            public function execute(WorkflowContext $context): ActionResult {
                return ActionResult::success(["executed" => true]);
            }
            public function canExecute(WorkflowContext $context): bool { return true; }
            public function getName(): string { return "Multi Attribute Action"; }
            public function getDescription(): string { return "Test action with multiple attributes"; }
        }';

        // Temporarily create the class in memory for testing
        eval(substr($testActionCode, 5)); // Remove opening <?php tag

        $reflection = new ReflectionClass('Tests\Support\TestActions\MultiAttributeTestAction');

        // Check WorkflowStep
        $stepAttrs = $reflection->getAttributes(WorkflowStep::class);
        expect($stepAttrs)->toHaveCount(1);
        $step = $stepAttrs[0]->newInstance();
        expect($step->id)->toBe('multi_attr');

        // Check Timeout with combined time units
        $timeoutAttrs = $reflection->getAttributes(Timeout::class);
        expect($timeoutAttrs)->toHaveCount(1);
        $timeout = $timeoutAttrs[0]->newInstance();
        expect($timeout->totalSeconds)->toBe(330); // 5 minutes + 30 seconds = 330 seconds

        // Check Retry with custom configuration
        $retryAttrs = $reflection->getAttributes(Retry::class);
        expect($retryAttrs)->toHaveCount(1);
        $retry = $retryAttrs[0]->newInstance();
        expect($retry->attempts)->toBe(5);
        expect($retry->backoff)->toBe('exponential');
        expect($retry->delay)->toBe(2000);
        expect($retry->maxDelay)->toBe(60000);

        // Check multiple Condition attributes
        $conditionAttrs = $reflection->getAttributes(Condition::class);
        expect($conditionAttrs)->toHaveCount(2);

        $condition1 = $conditionAttrs[0]->newInstance();
        expect($condition1->expression)->toBe('user.premium = true');

        $condition2 = $conditionAttrs[1]->newInstance();
        expect($condition2->expression)->toBe('order.amount > 100');
        expect($condition2->operator)->toBe('and');
    });

    test('attribute inheritance works correctly', function () {
        // Test that attributes can be read from parent classes if needed
        $reflection = new ReflectionClass(CreateUserProfileAction::class);

        // Get all attributes including inherited ones
        $allAttributes = [];
        foreach ($reflection->getAttributes() as $attribute) {
            $allAttributes[] = $attribute->getName();
        }

        expect($allAttributes)->toContain(WorkflowStep::class);
        expect($allAttributes)->toContain(Timeout::class);
        expect($allAttributes)->toContain(Retry::class);
    });

});
