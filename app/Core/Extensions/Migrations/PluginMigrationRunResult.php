<?php

namespace App\Core\Extensions\Migrations;

use App\Core\Extensions\Models\ExtensionRecord;

class PluginMigrationRunResult
{
    public function __construct(
        protected bool $success,
        protected bool $changed,
        protected string $message,
        protected ?ExtensionRecord $record = null,
        protected ?PluginMigrationStatus $status = null,
        protected ?int $exitCode = null,
    ) {
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function changed(): bool
    {
        return $this->changed;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function record(): ?ExtensionRecord
    {
        return $this->record;
    }

    public function status(): ?PluginMigrationStatus
    {
        return $this->status;
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success(),
            'changed' => $this->changed(),
            'message' => $this->message(),
            'exit_code' => $this->exitCode(),
            'record' => $this->record()?->toRegistryArray(),
            'status' => $this->status()?->toArray(),
        ];
    }
}
