<?php

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Models\ExtensionRecord;

class ExtensionStateChangeResult
{
    public function __construct(
        protected bool $success,
        protected bool $changed,
        protected string $message,
        protected ?ExtensionRecord $record = null,
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

    public function toArray(): array
    {
        return [
            'success' => $this->success(),
            'changed' => $this->changed(),
            'message' => $this->message(),
            'record' => $this->record()?->toRegistryArray(),
        ];
    }
}
