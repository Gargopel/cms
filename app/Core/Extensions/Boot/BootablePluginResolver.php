<?php

namespace App\Core\Extensions\Boot;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BootablePluginResolver
{
    public function resolve(): BootablePluginResolution
    {
        try {
            if (! Schema::hasTable('extension_records')) {
                return new BootablePluginResolution(
                    candidates: [],
                    ignored: [],
                    systemErrors: [[
                        'reason' => 'registry_table_missing',
                        'message' => 'Extension registry table [extension_records] is not available.',
                    ]],
                );
            }

            $records = ExtensionRecord::query()
                ->orderBy('type')
                ->orderBy('slug')
                ->orderBy('id')
                ->get();
        } catch (Throwable $exception) {
            return new BootablePluginResolution(
                candidates: [],
                ignored: [],
                systemErrors: [[
                    'reason' => 'registry_unavailable',
                    'message' => $exception->getMessage(),
                ]],
            );
        }

        $candidates = [];
        $ignored = [];

        foreach ($records as $record) {
            if ($record->type !== ExtensionType::Plugin) {
                $ignored[] = $this->ignoredEntry($record, 'not_a_plugin');

                continue;
            }

            if ($record->discovery_status !== ExtensionDiscoveryStatus::Valid) {
                $ignored[] = $this->ignoredEntry(
                    $record,
                    'discovery_status_not_valid',
                    ['discovery_status' => $record->discovery_status?->value]
                );

                continue;
            }

            if ($record->operational_status !== ExtensionOperationalStatus::Enabled) {
                $ignored[] = $this->ignoredEntry(
                    $record,
                    'operational_status_not_enabled',
                    ['operational_status' => $record->operational_status?->value]
                );

                continue;
            }

            $provider = $record->declaredProvider();

            if ($provider === null) {
                $ignored[] = $this->ignoredEntry($record, 'provider_missing');

                continue;
            }

            $candidates[] = new BootablePluginCandidate($record, $provider);
        }

        return new BootablePluginResolution(
            candidates: $candidates,
            ignored: $ignored,
        );
    }

    protected function ignoredEntry(ExtensionRecord $record, string $reason, array $context = []): array
    {
        return array_merge($record->toRegistryArray(), [
            'reason' => $reason,
        ], $context);
    }
}
