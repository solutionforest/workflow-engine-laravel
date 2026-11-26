<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SolutionForest\WorkflowEngine\Actions\LogAction;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Contracts\WorkflowAction;
use SolutionForest\WorkflowEngine\Core\ActionResult;
use SolutionForest\WorkflowEngine\Core\WorkflowContext;
use SolutionForest\WorkflowEngine\Core\WorkflowEngine;
use SolutionForest\WorkflowEngine\Core\WorkflowState;
use SolutionForest\WorkflowEngine\Exceptions\ConcurrentModificationException;
use SolutionForest\WorkflowEngine\Laravel\Jobs\SignalWorkflow;
use SolutionForest\WorkflowEngine\Laravel\Jobs\StartWorkflow;
use SolutionForest\WorkflowEngine\Laravel\Models\WorkflowInstance;
use SolutionForest\WorkflowEngine\Laravel\Storage\DatabaseStorage;

/** Waits for "decided", or until the configured deadline. */
class AwaitDecisionAction implements WorkflowAction
{
    /** @param array<string, mixed> $config */
    public function __construct(public array $config = [], public mixed $logger = null) {}

    public function execute(WorkflowContext $context): ActionResult
    {
        $deadline = $this->config['deadline'] ?? null;

        return ActionResult::suspend('decided', is_string($deadline) ? new DateTimeImmutable($deadline) : null);
    }

    public function canExecute(WorkflowContext $context): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'Await decision';
    }

    public function getDescription(): string
    {
        return 'Waits for a decision';
    }
}

/** @return array<string, mixed> */
function decisionFlow(?string $deadline = null): array
{
    return [
        'name' => 'decision',
        'steps' => [
            ['id' => 'decide', 'action' => AwaitDecisionAction::class, 'config' => $deadline === null ? [] : ['deadline' => $deadline]],
            ['id' => 'done', 'action' => LogAction::class, 'config' => ['message' => 'done']],
        ],
        'transitions' => [['from' => 'decide', 'to' => 'done']],
    ];
}

beforeEach(function () {
    $this->engine = app(WorkflowEngine::class);
    $this->storage = app(StorageAdapter::class);
});

test('a suspended workflow is stored with what it waits for, and a signal finishes it', function () {
    $this->engine->start('wf-1', decisionFlow('2030-01-01 00:00:00'), [
        DatabaseStorage::META => ['tenant_id' => 't-1', 'subject_type' => 'request', 'subject_id' => 42],
    ]);

    $row = WorkflowInstance::query()->findOrFail('wf-1');
    expect($this->storage)->toBeInstanceOf(DatabaseStorage::class)
        ->and($row->state)->toBe(WorkflowState::WAITING)
        ->and($row->waits_for)->toBe('decided')
        ->and($row->resume_at?->format('Y-m-d H:i'))->toBe('2030-01-01 00:00')
        ->and($row->wait_state['step_id'] ?? null)->toBe('decide')
        ->and($row->tenant_id)->toBe('t-1')
        ->and($row->subject_type)->toBe('request')
        ->and($row->subject_id)->toBe('42')
        ->and($row->definition_fingerprint)->toHaveLength(64)
        ->and($row->revision)->toBeGreaterThan(0);

    $this->engine->signal('wf-1', 'decided', ['by' => 'ada']);

    $row->refresh();
    expect($row->state)->toBe(WorkflowState::COMPLETED)
        ->and($row->waits_for)->toBeNull()
        ->and($row->resume_at)->toBeNull()
        ->and($row->completed_steps)->toBe(['decide', 'done'])
        ->and($row->data['steps']['decide']['payload'] ?? null)->toBe(['by' => 'ada']);
});

test('finds instances by owner', function () {
    $this->engine->start('wf-1', decisionFlow(), [DatabaseStorage::META => ['subject_type' => 'request', 'subject_id' => '1']]);
    $this->engine->start('wf-2', decisionFlow(), [DatabaseStorage::META => ['subject_type' => 'request', 'subject_id' => '2']]);

    $found = $this->storage->findInstances(['subject_type' => 'request', 'subject_id' => '2']);

    expect(array_map(fn ($i) => $i->getId(), $found))->toBe(['wf-2']);
});

test('a save from a stale copy is refused and writes nothing', function () {
    $this->engine->start('wf-1', decisionFlow());
    $stale = $this->storage->load('wf-1');
    $this->engine->signal('wf-1', 'decided');

    $stale->setErrorMessage('overwritten');

    expect(fn () => $this->storage->save($stale))->toThrow(ConcurrentModificationException::class);
    expect(WorkflowInstance::query()->findOrFail('wf-1')->error_message)->toBeNull();
});

test('two first saves of the same id: the second is refused', function () {
    $this->engine->start('wf-1', decisionFlow());
    $copy = $this->storage->load('wf-1');
    $copy->setRevision(0);

    expect(fn () => $this->storage->save($copy))->toThrow(ConcurrentModificationException::class);
});

test('workflow:resume-due resumes waits whose time has passed, and only those', function () {
    $this->engine->start('past', decisionFlow('2000-01-01 00:00:00'));
    $this->engine->start('future', decisionFlow('2999-01-01 00:00:00'));
    $this->engine->start('no-deadline', decisionFlow());

    expect(Artisan::call('workflow:resume-due'))->toBe(0)
        ->and(Artisan::output())->toContain('1 workflow(s) resumed');

    expect(WorkflowInstance::query()->findOrFail('past')->state)->toBe(WorkflowState::COMPLETED)
        ->and(WorkflowInstance::query()->findOrFail('future')->state)->toBe(WorkflowState::WAITING)
        ->and(WorkflowInstance::query()->findOrFail('no-deadline')->state)->toBe(WorkflowState::WAITING);
});

test('schedules workflow:resume-due every minute unless switched off', function () {
    $events = collect(app(Schedule::class)->events())->map(fn ($e) => (string) $e->command);

    expect($events->contains(fn (string $c) => str_contains($c, 'workflow:resume-due')))->toBeTrue();
});

test('the SignalWorkflow job delivers the signal, and releases itself on a conflict', function () {
    $this->engine->start('wf-1', decisionFlow());

    SignalWorkflow::dispatchSync('wf-1', 'decided', ['by' => 'job']);
    expect(WorkflowInstance::query()->findOrFail('wf-1')->state)->toBe(WorkflowState::COMPLETED);

    $engine = Mockery::mock(WorkflowEngine::class);
    $engine->shouldReceive('signal')->andThrow(new ConcurrentModificationException('wf-2', 1, 2));
    $job = (new SignalWorkflow('wf-2', 'decided'))->withFakeQueueInteractions();
    $job->handle($engine);
    $job->assertReleased();
});

test('the StartWorkflow job does not start the same workflow twice', function () {
    StartWorkflow::dispatchSync('wf-1', decisionFlow());
    StartWorkflow::dispatchSync('wf-1', decisionFlow());

    expect(WorkflowInstance::query()->count())->toBe(1);
});

test('the upgrade migration brings a v1 table to v2, and its rows stay writable', function () {
    Schema::drop('workflow_instances');
    Schema::create('workflow_instances', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('definition_name');
        $table->string('definition_version')->default('1.0');
        $table->longText('definition_data');
        $table->string('state');
        $table->longText('data')->nullable();
        $table->string('current_step_id')->nullable();
        $table->json('completed_steps')->nullable();
        $table->json('failed_steps')->nullable();
        $table->text('error_message')->nullable();
        $table->timestamps();
        $table->index(['state']);
    });
    DB::table('workflow_instances')->insert([
        'id' => 'old', 'definition_name' => 'decision', 'definition_version' => '1.0',
        'definition_data' => json_encode(decisionFlow()), 'state' => 'failed', 'data' => '{}',
        'completed_steps' => '[]', 'failed_steps' => '[]', 'error_message' => 'boom',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    (require __DIR__.'/../../database/migrations/upgrade_workflow_instances_table_to_v2.php.stub')->up();

    $old = $this->storage->load('old');
    expect($old->getRevision())->toBe(1);
    $old->setErrorMessage(null);
    $this->storage->save($old);
    expect(WorkflowInstance::query()->findOrFail('old')->revision)->toBe(2);
});
