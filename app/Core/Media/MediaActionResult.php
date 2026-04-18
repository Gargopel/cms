<?php

namespace App\Core\Media;

use App\Core\Media\Models\MediaAsset;

class MediaActionResult
{
    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(
        protected bool $success,
        protected string $message,
        protected ?MediaAsset $asset = null,
        protected array $reasons = [],
    ) {
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function asset(): ?MediaAsset
    {
        return $this->asset;
    }

    /**
     * @return array<int, string>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }
}
