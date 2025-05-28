<?php

namespace SolutionForest\WorkflowEngine\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use SolutionForest\WorkflowEngine\Core\WorkflowDefinition;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance as CoreWorkflowInstance;
use SolutionForest\WorkflowEngine\Core\WorkflowState;

/**
 * @property string $id
 * @property string $definition_name
 * @property string $definition_version
 * @property array $definition_data
 * @property WorkflowState $state
 * @property array $data
 * @property string|null $current_step_id
 * @property array $completed_steps
 * @property array $failed_steps
 * @property string|null $error_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';

    protected $fillable = [
        'id',
        'definition_name',
        'definition_version',
        'definition_data',
        'state',
        'data',
        'current_step_id',
        'completed_steps',
        'failed_steps',
        'error_message',
    ];

    protected $casts = [
        'definition_data' => 'array',
        'data' => 'array',
        'completed_steps' => 'array',
        'failed_steps' => 'array',
        'state' => WorkflowState::class,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Convert this Eloquent model to a core WorkflowInstance
     */
    public function toCoreInstance(): CoreWorkflowInstance
    {
        $definition = WorkflowDefinition::fromArray($this->definition_data);

        return CoreWorkflowInstance::fromArray([
            'id' => $this->id,
            'state' => $this->state->value,
            'data' => $this->data,
            'current_step_id' => $this->current_step_id,
            'completed_steps' => $this->completed_steps,
            'failed_steps' => $this->failed_steps,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ], $definition);
    }

    /**
     * Create an Eloquent model from a core WorkflowInstance
     */
    public static function fromCoreInstance(CoreWorkflowInstance $instance): self
    {
        return new self([
            'id' => $instance->getId(),
            'definition_name' => $instance->getDefinition()->getName(),
            'definition_version' => $instance->getDefinition()->getVersion(),
            'definition_data' => $instance->getDefinition()->toArray(),
            'state' => $instance->getState(),
            'data' => $instance->getData(),
            'current_step_id' => $instance->getCurrentStepId(),
            'completed_steps' => $instance->getCompletedSteps(),
            'failed_steps' => $instance->getFailedSteps(),
            'error_message' => $instance->getErrorMessage(),
            'created_at' => $instance->getCreatedAt(),
            'updated_at' => $instance->getUpdatedAt(),
        ]);
    }
}
