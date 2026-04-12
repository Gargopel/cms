<?php

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Discovery\DiscoveredExtension;
use App\Core\Extensions\Discovery\ExtensionDiscoveryService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Permissions\PluginPermissionRegistrySynchronizer;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExtensionRegistrySynchronizer
{
    public function __construct(
        protected ExtensionDiscoveryService $discovery,
        protected PluginPermissionRegistrySynchronizer $pluginPermissions,
    ) {
    }

    public function sync(): ExtensionRegistrySyncResult
    {
        $discoveryResult = $this->discovery->discover();
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $forcedDisabled = 0;
        $records = [];

        DB::transaction(function () use (
            $discoveryResult,
            &$created,
            &$updated,
            &$unchanged,
            &$forcedDisabled,
            &$records,
        ): void {
            foreach ($discoveryResult->all() as $extension) {
                [$record, $wasCreated, $wasUpdated, $wasForcedDisabled] = $this->syncExtension($extension);

                $records[] = $record->fresh();
                $created += $wasCreated ? 1 : 0;
                $updated += $wasUpdated ? 1 : 0;
                $unchanged += (! $wasCreated && ! $wasUpdated) ? 1 : 0;
                $forcedDisabled += $wasForcedDisabled ? 1 : 0;
            }
        });

        $this->pluginPermissions->sync();

        return new ExtensionRegistrySyncResult(
            coreVersion: $discoveryResult->coreVersion(),
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
            forcedDisabled: $forcedDisabled,
            records: $records,
        );
    }

    /**
     * @return array{0: ExtensionRecord, 1: bool, 2: bool, 3: bool}
     */
    protected function syncExtension(DiscoveredExtension $extension): array
    {
        $record = $this->findExistingRecord($extension);
        $created = false;
        $forcedDisabled = false;
        $now = Carbon::now();

        if (! $record instanceof ExtensionRecord) {
            $record = new ExtensionRecord();
            $record->type = $extension->type();
            $record->path = $extension->path();
            $record->lifecycle_status = ExtensionLifecycleStatus::Discovered;
            $record->operational_status = ExtensionOperationalStatus::Discovered;
            $created = true;
        }

        $record->fill($this->attributesFromDiscoveredExtension($extension, $now));

        if (! $record->lifecycle_status instanceof ExtensionLifecycleStatus) {
            $record->lifecycle_status = $record->administrativeLifecycleStatus();
        }

        if (
            $record->operational_status === ExtensionOperationalStatus::Enabled &&
            $record->discovery_status !== ExtensionDiscoveryStatus::Valid
        ) {
            $record->operational_status = ExtensionOperationalStatus::Disabled;
            $forcedDisabled = true;
        }

        if (
            $record->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Removed &&
            $record->operational_status === ExtensionOperationalStatus::Enabled
        ) {
            $record->operational_status = ExtensionOperationalStatus::Disabled;
            $forcedDisabled = true;
        }

        $wasUpdated = $created || $record->isDirty();

        if ($wasUpdated) {
            $record->save();
        } elseif (! $record->exists) {
            $record->save();
        }

        return [$record, $created, $wasUpdated && ! $created, $forcedDisabled];
    }

    protected function findExistingRecord(DiscoveredExtension $extension): ?ExtensionRecord
    {
        $query = ExtensionRecord::query()
            ->where('type', $extension->type()->value)
            ->where('path', $extension->path());

        $record = $query->first();

        if ($record instanceof ExtensionRecord) {
            return $record;
        }

        $slug = $this->extractSlug($extension);

        if ($slug === null) {
            return null;
        }

        return ExtensionRecord::query()
            ->where('type', $extension->type()->value)
            ->where('slug', $slug)
            ->first();
    }

    protected function attributesFromDiscoveredExtension(DiscoveredExtension $extension, Carbon $timestamp): array
    {
        return [
            'type' => $extension->type(),
            'slug' => $this->extractSlug($extension),
            'name' => $this->extractValue($extension, 'name'),
            'description' => $this->extractValue($extension, 'description'),
            'author' => $this->extractValue($extension, 'author'),
            'detected_version' => $this->extractValue($extension, 'version'),
            'path' => $extension->path(),
            'manifest_path' => $extension->manifestPath(),
            'discovery_status' => $extension->status(),
            'discovery_errors' => $extension->errors(),
            'raw_manifest' => $extension->rawManifest(),
            'normalized_manifest' => $extension->normalizedManifest(),
            'manifest_warnings' => $extension->warnings(),
            'last_seen_at' => $timestamp,
            'last_synced_at' => $timestamp,
        ];
    }

    protected function extractSlug(DiscoveredExtension $extension): ?string
    {
        return $this->extractValue($extension, 'slug');
    }

    protected function extractValue(DiscoveredExtension $extension, string $key): ?string
    {
        if ($extension->manifest() !== null) {
            return match ($key) {
                'name' => $extension->manifest()->name(),
                'slug' => $extension->manifest()->slug(),
                'description' => $extension->manifest()->description(),
                'version' => $extension->manifest()->version(),
                'author' => $extension->manifest()->author(),
                default => null,
            };
        }

        $value = $extension->rawManifest()[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
