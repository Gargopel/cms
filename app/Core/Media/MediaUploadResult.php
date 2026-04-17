<?php

namespace App\Core\Media;

use App\Core\Media\Models\MediaAsset;

class MediaUploadResult
{
    public function __construct(
        protected bool $success,
        protected string $message,
        protected ?MediaAsset $asset = null,
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
}
