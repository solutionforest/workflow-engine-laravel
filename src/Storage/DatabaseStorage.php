<?php

namespace SolutionForest\WorkflowEngine\Laravel\Storage;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowDefinition;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;

class DatabaseStorage implements StorageAdapter
{
    private DatabaseManager $db;

    private string $table;

    public function __construct(DatabaseManager $db, string $table = 'workflow_instances')
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function save(WorkflowInstance $instance): void
    {
        $data = [
            'id' => $instance->getId(),
            'definition_name' => $instance->getDefinition()->getName(),
            'definition_version' => $instance->getDefinition()->getVersion(),
            'definition_data' => json_encode($instance->getDefinition()->toArray()),
            'state' => $instance->getState()->value,
            'data' => json_encode($instance->getData()),
            'current_step_id' => $instance->getCurrentStepId(),
            'completed_steps' => json_encode($instance->getCompletedSteps()),
            'failed_steps' => json_encode($instance->getFailedSteps()),
            'error_message' => $instance->getErrorMessage(),
            'created_at' => $instance->getCreatedAt(),
            'updated_at' => $instance->getUpdatedAt(),
        ];

        if ($this->exists($instance->getId())) {
            $this->query()->where('id', $instance->getId())->update($data);
        } else {
            $this->query()->insert($data);
        }
    }

    public function load(string $id): WorkflowInstance
    {
        $row = $this->query()->where('id', $id)->first();

        if (! $row) {
            throw new \InvalidArgumentException("Workflow instance {$id} not found");
        }

        $definitionData = json_decode($row->definition_data, true);
        $definition = WorkflowDefinition::fromArray($definitionData);

        return WorkflowInstance::fromArray([
            'id' => $row->id,
            'state' => $row->state,
            'data' => json_decode($row->data, true),
            'current_step_id' => $row->current_step_id,
            'completed_steps' => json_decode($row->completed_steps, true),
            'failed_steps' => json_decode($row->failed_steps, true),
            'error_message' => $row->error_message,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ], $definition);
    }

    public function findInstances(array $criteria = []): array
    {
        $query = $this->query();

        if (isset($criteria['state'])) {
            $query->where('state', $criteria['state']);
        }

        if (isset($criteria['definition_name'])) {
            $query->where('definition_name', $criteria['definition_name']);
        }

        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }

        if (isset($criteria['created_before'])) {
            $query->where('created_at', '<=', $criteria['created_before']);
        }

        $limit = $criteria['limit'] ?? 100;
        $offset = $criteria['offset'] ?? 0;

        $rows = $query->limit($limit)->offset($offset)->get();

        $instances = [];
        foreach ($rows as $row) {
            $definitionData = json_decode($row->definition_data, true);
            $definition = WorkflowDefinition::fromArray($definitionData);

            $instances[] = WorkflowInstance::fromArray([
                'id' => $row->id,
                'state' => $row->state,
                'data' => json_decode($row->data, true),
                'current_step_id' => $row->current_step_id,
                'completed_steps' => json_decode($row->completed_steps, true),
                'failed_steps' => json_decode($row->failed_steps, true),
                'error_message' => $row->error_message,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ], $definition);
        }

        return $instances;
    }

    public function delete(string $id): void
    {
        $this->query()->where('id', $id)->delete();
    }

    public function exists(string $id): bool
    {
        return $this->query()->where('id', $id)->exists();
    }

    public function updateState(string $id, array $updates): void
    {
        $this->query()->where('id', $id)->update($updates);
    }

    private function query(): Builder
    {
        return $this->db->table($this->table);
    }
}
