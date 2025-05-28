<?php

namespace SolutionForest\WorkflowMastery\Core;

class ActionResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $errorMessage = null,
        private readonly array $data = [],
        private readonly array $metadata = []
    ) {}

    public static function success(array $data = [], array $metadata = []): self
    {
        return new self(true, null, $data, $metadata);
    }

    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(false, $errorMessage, [], $metadata);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function hasData(): bool
    {
        return ! empty($this->data);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'error_message' => $this->errorMessage,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
