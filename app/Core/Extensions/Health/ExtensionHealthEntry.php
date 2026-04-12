<?php

namespace App\Core\Extensions\Health;

use App\Core\Health\Enums\HealthStatus;

class ExtensionHealthEntry
{
    /**
     * @param  array<int, array<string, mixed>>  $issues
     */
    public function __construct(
        protected int|string|null $id,
        protected ?string $slug,
        protected string $name,
        protected ?string $type,
        protected HealthStatus $status,
        protected array $issues = [],
    ) {
    }

    public function id(): int|string|null
    {
        return $this->id;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function status(): HealthStatus
    {
        return $this->status;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function issues(): array
    {
        return $this->issues;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'slug' => $this->slug(),
            'name' => $this->name(),
            'type' => $this->type(),
            'status' => $this->status()->value,
            'issues' => $this->issues(),
        ];
    }
}
