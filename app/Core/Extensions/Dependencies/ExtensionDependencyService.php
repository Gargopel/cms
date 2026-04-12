<?php

namespace App\Core\Extensions\Dependencies;

use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Models\ExtensionRecord;

class ExtensionDependencyService
{
    public function inspect(ExtensionRecord $record): ExtensionDependencyInspection
    {
        $requirements = $this->inspectRequirements($record);
        $activeDependents = $this->inspectActiveDependents($record);
        $warnings = [];

        foreach ($requirements as $requirement) {
            if (($requirement['status'] ?? null) === 'ambiguous') {
                $warnings[] = [
                    'code' => 'ambiguous_dependency_slug',
                    'message' => sprintf(
                        'A dependencia [%s] corresponde a mais de um registro e foi resolvida usando o melhor estado operacional disponivel.',
                        $requirement['slug'],
                    ),
                    'context' => [
                        'slug' => $requirement['slug'],
                        'matches' => $requirement['matches'],
                    ],
                ];
            }
        }

        return new ExtensionDependencyInspection(
            requirements: $requirements,
            activeDependents: $activeDependents,
            warnings: $warnings,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inspectRequirements(ExtensionRecord $record): array
    {
        $requirements = [];

        foreach ($record->declaredDependencies() as $dependencySlug) {
            $matches = ExtensionRecord::query()
                ->where('slug', $dependencySlug)
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            if ($matches->isEmpty()) {
                $requirements[] = [
                    'slug' => $dependencySlug,
                    'status' => 'missing',
                    'matches' => [],
                ];

                continue;
            }

            $enabledMatches = $matches
                ->filter(static fn (ExtensionRecord $match): bool => $match->operational_status === ExtensionOperationalStatus::Enabled)
                ->values();

            $status = $enabledMatches->isNotEmpty() ? 'enabled' : 'disabled';

            if ($matches->count() > 1) {
                $status = $enabledMatches->isNotEmpty() ? 'ambiguous' : 'disabled';
            }

            $requirements[] = [
                'slug' => $dependencySlug,
                'status' => $status,
                'matches' => $matches
                    ->map(fn (ExtensionRecord $match): array => $this->summarizeRecord($match))
                    ->values()
                    ->all(),
            ];
        }

        return $requirements;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function inspectActiveDependents(ExtensionRecord $record): array
    {
        if (blank($record->slug)) {
            return [];
        }

        return ExtensionRecord::query()
            ->where('id', '!=', $record->getKey())
            ->where('operational_status', ExtensionOperationalStatus::Enabled->value)
            ->get()
            ->filter(function (ExtensionRecord $candidate) use ($record): bool {
                return in_array($record->slug, $candidate->declaredDependencies(), true);
            })
            ->map(fn (ExtensionRecord $candidate): array => $this->summarizeRecord($candidate))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarizeRecord(ExtensionRecord $record): array
    {
        return [
            'id' => $record->getKey(),
            'slug' => $record->slug,
            'name' => $record->name,
            'type' => $record->type?->value,
            'discovery_status' => $record->discovery_status?->value,
            'operational_status' => $record->operational_status?->value,
        ];
    }
}
