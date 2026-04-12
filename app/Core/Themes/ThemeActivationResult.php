<?php

namespace App\Core\Themes;

use App\Core\Extensions\Models\ExtensionRecord;

class ThemeActivationResult
{
    public function __construct(
        protected bool $success,
        protected bool $changed,
        protected string $message,
        protected ?ExtensionRecord $theme = null,
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

    public function theme(): ?ExtensionRecord
    {
        return $this->theme;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success(),
            'changed' => $this->changed(),
            'message' => $this->message(),
            'theme' => $this->theme()?->toRegistryArray(),
        ];
    }
}
