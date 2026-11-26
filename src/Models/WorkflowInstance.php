<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance as CoreWorkflowInstance;
use SolutionForest\WorkflowEngine\Core\WorkflowState;

/**
 * Read model of the `workflow_instances` table, for queries, admin screens and relations
 * (`$model->workflows()` via `subject`). Writes go through the engine and DatabaseStorage,
 * which check the revision; do not save this model.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string $definition_name
 * @property string $definition_version
 * @property string|null $definition_fingerprint
 * @property array<string, mixed> $definition_data
 * @property WorkflowState $state
 * @property array<string, mixed>|null $data
 * @property string|null $current_step_id
 * @property list<string>|null $completed_steps
 * @property array<int, array<string, string>>|null $failed_steps
 * @property string|null $error_message
 * @property array<string, mixed>|null $wait_state
 * @property string|null $waits_for
 * @property Carbon|null $resume_at
 * @property int $revision
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';

    protected $guarded = ['*'];

    public $incrementing = false;

    protected $keyType = 'string';

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'definition_data' => 'array',
            'data' => 'array',
            'completed_steps' => 'array',
            'failed_steps' => 'array',
            'wait_state' => 'array',
            'state' => WorkflowState::class,
            'resume_at' => 'datetime',
            'revision' => 'integer',
        ];
    }

    public function getTable(): string
    {
        $table = config('workflow-engine.storage.database.table');

        return is_string($table) && $table !== '' ? $table : 'workflow_instances';
    }

    /**
     * The record this workflow runs for (`_meta.subject_type` / `subject_id` at start).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** The engine's instance, loaded through storage (revision included). */
    public function toCoreInstance(): CoreWorkflowInstance
    {
        return app(StorageAdapter::class)->load($this->id);
    }
}
