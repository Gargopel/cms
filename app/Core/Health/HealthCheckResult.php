<?php

namespace App\Core\Health;

use App\Core\Health\Enums\HealthStatus;

class HealthCheckResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly HealthStatus $status,
        public readonly string $message,
        public readonly array $meta = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'status' => $this->status->value,
            'message' => $this->message,
            'meta' => $this->meta,
        ];
    }
}
