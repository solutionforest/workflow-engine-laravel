<?php

declare(strict_types=1);

namespace SolutionForest\WorkflowEngine\Laravel\Storage;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use SolutionForest\WorkflowEngine\Contracts\StorageAdapter;
use SolutionForest\WorkflowEngine\Core\WorkflowDefinition;
use SolutionForest\WorkflowEngine\Core\WorkflowInstance;
use SolutionForest\WorkflowEngine\Core\WorkflowState;
use SolutionForest\WorkflowEngine\Exceptions\ConcurrentModificationException;
use SolutionForest\WorkflowEngine\Exceptions\WorkflowInstanceNotFoundException;

/**
 * Stores workflow instances in one table (see the package migration).
 *
 * Every save is a compare-and-swap on `revision`: the UPDATE only matches the row when its
 * revision is the one this copy was loaded with. When someone else saved in between, nothing is
 * written and ConcurrentModificationException is thrown, so two queue workers or two approvers
 * cannot overwrite each other.
 *
 * Owner columns: put `_meta` => ['tenant_id' => …, 'subject_type' => …, 'subject_id' => …] in
 * the workflow data when you start it, and the instance can be found by tenant and by the record
 * it belongs to (`findInstances(['subject_type' => …, 'subject_id' => …])`).
 */
class DatabaseStorage implements StorageAdapter
{
    public const string META = '_meta';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly string $table = 'workflow_instances',
        private readonly ?string $connection = null,
    ) {}

    public function save(WorkflowInstance $instance): void
    {
        $expected = $instance->getRevision();
        $row = $this->row($instance, $expected + 1);

        if ($expected === 0) {
            try {
                $this->query()->insert($row);
            } catch (UniqueConstraintViolationException) {
                throw new ConcurrentModificationException($instance->getId(), 0, $this->storedRevision($instance->getId()));
            }
        } else {
            unset($row['id'], $row['created_at']);
            $updated = $this->query()
                ->where('id', $instance->getId())
                ->where('revision', $expected)
                ->update($row);
            if ($updated !== 1) {
                throw new ConcurrentModificationException($instance->getId(), $expected, $this->storedRevision($instance->getId()));
            }
        }

        $instance->setRevision($expected + 1);
    }

    public function load(string $id): WorkflowInstance
    {
        $row = $this->query()->where('id', $id)->first();
        if ($row === null) {
            throw WorkflowInstanceNotFoundException::notFound($id, self::class);
        }

        return $this->instance((array) $row);
    }

    /**
     * Criteria: `state` (string or WorkflowState), `definition_name`, `tenant_id`,
     * `subject_type` + `subject_id`, `due_before` (DateTimeInterface: WAITING instances whose
     * resume time has passed), `created_after`, `created_before`, `limit` (default 100), `offset`.
     *
     * @param  array<string, mixed>  $criteria
     * @return list<WorkflowInstance>
     */
    public function findInstances(array $criteria = []): array
    {
        $query = $this->query();

        if (isset($criteria['state'])) {
            $state = $criteria['state'];
            $query->where('state', $state instanceof WorkflowState ? $state->value : $state);
        }
        foreach (['definition_name', 'tenant_id', 'subject_type', 'subject_id'] as $column) {
            if (isset($criteria[$column])) {
                $query->where($column, $criteria[$column]);
            }
        }
        if (($criteria['due_before'] ?? null) instanceof \DateTimeInterface) {
            $query->where('state', WorkflowState::WAITING->value)
                ->whereNotNull('resume_at')
                ->where('resume_at', '<=', $this->timestamp($criteria['due_before']))
                ->orderBy('resume_at');
        }
        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }
        if (isset($criteria['created_before'])) {
            $query->where('created_at', '<=', $criteria['created_before']);
        }

        $limit = is_int($criteria['limit'] ?? null) ? $criteria['limit'] : 100;
        $offset = is_int($criteria['offset'] ?? null) ? $criteria['offset'] : 0;

        return array_values($query->limit($limit)->offset($offset)->get()
            ->map(fn (object $row): WorkflowInstance => $this->instance((array) $row))
            ->all());
    }

    public function delete(string $id): void
    {
        $this->query()->where('id', $id)->delete();
    }

    public function exists(string $id): bool
    {
        return $this->query()->where('id', $id)->exists();
    }

    /**
     * Apply a partial update (`data` merged, `error_message`, `current_step_id`, `state`) through a
     * normal, revision-checked save.
     *
     * @param  array<string, mixed>  $updates
     */
    public function updateState(string $id, array $updates): void
    {
        $instance = $this->load($id);
        if (isset($updates['data']) && is_array($updates['data'])) {
            /** @var array<string, mixed> $data */
            $data = $updates['data'];
            $instance->mergeData($data);
        }
        if (array_key_exists('error_message', $updates)) {
            $instance->setErrorMessage(is_string($updates['error_message']) ? $updates['error_message'] : null);
        }
        if (is_string($updates['current_step_id'] ?? null)) {
            $instance->setCurrentStepId($updates['current_step_id']);
        }
        // `state` is written as given, without the state machine's transition rules: this is
        // the repair door for operators and tests, not something steps use.
        $state = $updates['state'] ?? null;
        if ($state instanceof WorkflowState || is_string($state)) {
            $row = $instance->toArray();
            $row['state'] = $state instanceof WorkflowState ? $state->value : $state;
            $repaired = WorkflowInstance::fromArray($row, $instance->getDefinition());
            $repaired->setRevision($instance->getRevision());
            $instance = $repaired;
        }
        $this->save($instance);
    }

    /** @return array<string, mixed> */
    private function row(WorkflowInstance $instance, int $revision): array
    {
        $meta = $instance->getData()[self::META] ?? [];
        $meta = is_array($meta) ? $meta : [];
        $wait = $instance->getWait();
        $scalar = fn (mixed $value): ?string => is_string($value) || is_int($value) ? (string) $value : null;

        return [
            'id' => $instance->getId(),
            'tenant_id' => $scalar($meta['tenant_id'] ?? null),
            'subject_type' => $scalar($meta['subject_type'] ?? null),
            'subject_id' => $scalar($meta['subject_id'] ?? null),
            'definition_name' => $instance->getDefinition()->getName(),
            'definition_version' => $instance->getDefinition()->getVersion(),
            'definition_fingerprint' => $instance->getDefinition()->fingerprint(),
            'definition_data' => $this->json($instance->getDefinition()->toArray()),
            'state' => $instance->getState()->value,
            'data' => $this->json($instance->getData()),
            'current_step_id' => $instance->getCurrentStepId(),
            'completed_steps' => $this->json($instance->getCompletedSteps()),
            'failed_steps' => $this->json($instance->getFailedSteps()),
            'error_message' => $instance->getErrorMessage(),
            'wait_state' => $wait === null ? null : $this->json($wait->toArray()),
            'waits_for' => $wait?->for,
            'resume_at' => $wait?->resumeAt === null ? null : $this->timestamp($wait->resumeAt),
            'revision' => $revision,
            'created_at' => $this->timestamp($instance->getCreatedAt()),
            'updated_at' => $this->timestamp($instance->getUpdatedAt()),
        ];
    }

    /** @param array<string, mixed> $row */
    private function instance(array $row): WorkflowInstance
    {
        $definition = $this->decode($row['definition_data'] ?? null);

        return WorkflowInstance::fromArray([
            'id' => $row['id'] ?? null,
            'state' => $row['state'] ?? null,
            'data' => $this->decode($row['data'] ?? null),
            'current_step_id' => $row['current_step_id'] ?? null,
            'completed_steps' => $this->decode($row['completed_steps'] ?? null),
            'failed_steps' => $this->decode($row['failed_steps'] ?? null),
            'error_message' => $row['error_message'] ?? null,
            'wait' => ($row['wait_state'] ?? null) === null ? null : $this->decode($row['wait_state']),
            'revision' => is_numeric($row['revision'] ?? null) ? (int) $row['revision'] : 0,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ], WorkflowDefinition::fromArray($definition));
    }

    private function storedRevision(string $id): int
    {
        $revision = $this->query()->where('id', $id)->value('revision');

        return is_numeric($revision) ? (int) $revision : 0;
    }

    /** @param array<mixed> $value */
    private function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<mixed> */
    private function decode(mixed $json): array
    {
        if (! is_string($json) || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** UTC, in a format every database compares as text and as a timestamp. */
    private function timestamp(\DateTimeInterface $at): string
    {
        return \DateTimeImmutable::createFromInterface($at)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function query(): Builder
    {
        return $this->connection()->table($this->table);
    }

    private function connection(): ConnectionInterface
    {
        return $this->db->connection($this->connection);
    }
}
