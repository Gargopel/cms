<?php

namespace App\Core\Extensions\Health;

use App\Core\Health\Enums\HealthStatus;
use Illuminate\Support\Collection;

class ExtensionHealthReport
{
    /**
     * @param  array<int, ExtensionHealthEntry>  $entries
     */
    public function __construct(
        protected array $entries = [],
    ) {
    }

    /**
     * @return array<int, ExtensionHealthEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    public function overallStatus(): HealthStatus
    {
        if ($this->summary()['error'] > 0) {
            return HealthStatus::Error;
        }

        if ($this->summary()['warning'] > 0) {
            return HealthStatus::Warning;
        }

        return HealthStatus::Ok;
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'total' => count($this->entries()),
            'ok' => collect($this->entries())->filter(
                static fn (ExtensionHealthEntry $entry): bool => $entry->status() === HealthStatus::Ok
            )->count(),
            'warning' => collect($this->entries())->filter(
                static fn (ExtensionHealthEntry $entry): bool => $entry->status() === HealthStatus::Warning
            )->count(),
            'error' => collect($this->entries())->filter(
                static fn (ExtensionHealthEntry $entry): bool => $entry->status() === HealthStatus::Error
            )->count(),
            'issues' => $this->issues()->count(),
        ];
    }

    public function entryFor(int|string|null $id): ?ExtensionHealthEntry
    {
        return collect($this->entries())
            ->first(static fn (ExtensionHealthEntry $entry): bool => $entry->id() === $id);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function issues(): Collection
    {
        return collect($this->entries())
            ->flatMap(static function (ExtensionHealthEntry $entry): array {
                return array_map(
                    static fn (array $issue): array => [
                        ...$issue,
                        'extension' => [
                            'id' => $entry->id(),
                            'slug' => $entry->slug(),
                            'name' => $entry->name(),
                            'type' => $entry->type(),
                        ],
                    ],
                    $entry->issues(),
                );
            })
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topIssues(int $limit = 5): array
    {
        return $this->issues()
            ->sortByDesc(static fn (array $issue): int => match ($issue['status'] ?? HealthStatus::Ok->value) {
                HealthStatus::Error->value => 2,
                HealthStatus::Warning->value => 1,
                default => 0,
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function toArray(): array
    {
        return [
            'overall_status' => $this->overallStatus()->value,
            'summary' => $this->summary(),
            'entries' => array_map(
                static fn (ExtensionHealthEntry $entry): array => $entry->toArray(),
                $this->entries(),
            ),
            'top_issues' => $this->topIssues(),
        ];
    }
}
