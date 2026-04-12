<?php

namespace App\Core\Extensions\Models;

use App\Core\Contracts\Extensions\ExtensionManifest;
use App\Core\Extensions\Capabilities\ExtensionCapabilitySet;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Manifests\PluginManifest;
use App\Core\Extensions\Manifests\ThemeManifest;
use App\Core\Extensions\Permissions\PluginPermissionDefinition;
use Illuminate\Database\Eloquent\Model;

class ExtensionRecord extends Model
{
    protected $fillable = [
        'type',
        'slug',
        'name',
        'description',
        'author',
        'detected_version',
        'path',
        'manifest_path',
        'discovery_status',
        'operational_status',
        'lifecycle_status',
        'discovery_errors',
        'raw_manifest',
        'normalized_manifest',
        'manifest_warnings',
        'last_seen_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExtensionType::class,
            'discovery_status' => ExtensionDiscoveryStatus::class,
            'operational_status' => ExtensionOperationalStatus::class,
            'lifecycle_status' => ExtensionLifecycleStatus::class,
            'discovery_errors' => 'array',
            'raw_manifest' => 'array',
            'normalized_manifest' => 'array',
            'manifest_warnings' => 'array',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isDiscoveryValid(): bool
    {
        return $this->discovery_status === ExtensionDiscoveryStatus::Valid;
    }

    public function canBeEnabled(): bool
    {
        return $this->isDiscoveryValid() && $this->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Installed;
    }

    public function administrativeLifecycleStatus(): ExtensionLifecycleStatus
    {
        if ($this->lifecycle_status instanceof ExtensionLifecycleStatus) {
            return $this->lifecycle_status;
        }

        return match ($this->operational_status) {
            ExtensionOperationalStatus::Enabled,
            ExtensionOperationalStatus::Disabled,
            ExtensionOperationalStatus::Installed => ExtensionLifecycleStatus::Installed,
            default => ExtensionLifecycleStatus::Discovered,
        };
    }

    public function isAdministrativelyInstalled(): bool
    {
        return $this->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Installed;
    }

    public function isAdministrativelyRemoved(): bool
    {
        return $this->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Removed;
    }

    public function declaredProvider(): ?string
    {
        $snapshot = $this->normalizedManifestSnapshot();

        return is_string($snapshot['provider'] ?? null) && trim($snapshot['provider']) !== ''
            ? trim($snapshot['provider'])
            : null;
    }

    public function isCriticalForOperations(): bool
    {
        return (bool) ($this->normalizedManifestSnapshot()['critical'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    public function declaredDependencies(): array
    {
        $requires = $this->normalizedManifestSnapshot()['requires'] ?? [];

        if (! is_array($requires)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $dependency): ?string => is_string($dependency) && trim($dependency) !== ''
                    ? trim($dependency)
                    : null,
                $requires,
            ),
        )));
    }

    public function capabilitySet(): ExtensionCapabilitySet
    {
        return app(\App\Core\Extensions\Capabilities\ExtensionCapabilityService::class)->forExtension($this);
    }

    /**
     * @return array<int, string>
     */
    public function declaredCapabilities(): array
    {
        return $this->capabilitySet()->all();
    }

    public function normalizedManifest(): ?ExtensionManifest
    {
        $snapshot = $this->normalizedManifestSnapshot();

        if ($snapshot === null || ! $this->canHydrateNormalizedManifestSnapshot($snapshot)) {
            return null;
        }

        return match ($this->type) {
            ExtensionType::Plugin => PluginManifest::fromArray($snapshot, $this->path, $this->manifest_path),
            ExtensionType::Theme => ThemeManifest::fromArray($snapshot, $this->path, $this->manifest_path),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function normalizedManifestSnapshot(): ?array
    {
        if (is_array($this->normalized_manifest) && $this->normalized_manifest !== []) {
            return $this->normalized_manifest;
        }

        if (! $this->name || ! $this->slug || ! $this->description || ! $this->detected_version || ! $this->author) {
            return null;
        }

        return [
            'type' => $this->type?->value,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->detected_version,
            'author' => $this->author,
            'vendor' => is_string($this->raw_manifest['vendor'] ?? null) ? $this->raw_manifest['vendor'] : null,
            'core' => array_filter([
                'min' => is_string($this->raw_manifest['core']['min'] ?? null) ? $this->raw_manifest['core']['min'] : config('platform.core.version'),
                'max' => is_string($this->raw_manifest['core']['max'] ?? null) ? $this->raw_manifest['core']['max'] : null,
            ], static fn (mixed $value): bool => $value !== null),
            'provider' => is_string($this->raw_manifest['provider'] ?? null) ? $this->raw_manifest['provider'] : null,
            'critical' => (bool) ($this->raw_manifest['critical'] ?? false),
            'requires' => is_array($this->raw_manifest['requires'] ?? null) ? $this->raw_manifest['requires'] : [],
            'capabilities' => $this->coerceLegacyCapabilities($this->raw_manifest['capabilities'] ?? []),
            'permissions' => $this->coerceLegacyPermissions($this->raw_manifest['permissions'] ?? []),
        ];
    }

    /**
     * @return array<int, PluginPermissionDefinition>
     */
    public function declaredPluginPermissions(): array
    {
        if ($this->type !== ExtensionType::Plugin || blank($this->slug)) {
            return [];
        }

        $permissions = $this->normalizedManifestSnapshot()['permissions'] ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        $definitions = [];

        foreach ($permissions as $permission) {
            if (! is_array($permission)) {
                continue;
            }

            $localSlug = $permission['slug'] ?? null;
            $name = $permission['name'] ?? null;
            $description = $permission['description'] ?? null;

            if (! is_string($localSlug) || trim($localSlug) === '' || ! is_string($name) || trim($name) === '') {
                continue;
            }

            $definitions[] = new PluginPermissionDefinition(
                pluginSlug: $this->slug,
                localSlug: trim($localSlug),
                name: trim($name),
                description: is_string($description) && trim($description) !== '' ? trim($description) : null,
            );
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function canHydrateNormalizedManifestSnapshot(array $snapshot): bool
    {
        foreach (['name', 'slug', 'description', 'version', 'author'] as $field) {
            if (! isset($snapshot[$field]) || ! is_string($snapshot[$field]) || trim($snapshot[$field]) === '') {
                return false;
            }
        }

        if (! isset($snapshot['core']) || ! is_array($snapshot['core'])) {
            return false;
        }

        return isset($snapshot['core']['min'])
            && is_string($snapshot['core']['min'])
            && trim($snapshot['core']['min']) !== '';
    }

    /**
     * @return array<int, string>
     */
    protected function coerceLegacyCapabilities(mixed $value): array
    {
        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return $normalized !== '' ? [$normalized] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return array_values(array_unique(array_filter(
                array_map(
                    static fn (mixed $capability): ?string => is_string($capability) && trim($capability) !== ''
                        ? strtolower(trim($capability))
                        : null,
                    $value,
                ),
            )));
        }

        $capabilities = [];

        foreach ($value as $capability => $enabled) {
            if (! is_string($capability) || trim($capability) === '') {
                continue;
            }

            if (in_array($enabled, [true, 1, '1', 'true', 'yes', 'on'], true)) {
                $capabilities[] = strtolower(trim($capability));
            }
        }

        return array_values(array_unique($capabilities));
    }

    /**
     * @return array<int, array{slug: string, name: string, description: ?string}>
     */
    protected function coerceLegacyPermissions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $permissions = [];
        $seen = [];

        foreach ($value as $permission) {
            if (! is_array($permission)) {
                continue;
            }

            $slug = $permission['slug'] ?? null;
            $name = $permission['name'] ?? null;

            if (! is_string($slug) || trim($slug) === '' || ! is_string($name) || trim($name) === '') {
                continue;
            }

            $normalizedSlug = strtolower(trim($slug));

            if (isset($seen[$normalizedSlug])) {
                continue;
            }

            $seen[$normalizedSlug] = true;

            $permissions[] = [
                'slug' => $normalizedSlug,
                'name' => trim($name),
                'description' => is_string($permission['description'] ?? null) && trim($permission['description']) !== ''
                    ? trim($permission['description'])
                    : null,
            ];
        }

        return $permissions;
    }

    public function toRegistryArray(): array
    {
        return [
            'id' => $this->getKey(),
            'type' => $this->type?->value,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'author' => $this->author,
            'detected_version' => $this->detected_version,
            'path' => $this->path,
            'manifest_path' => $this->manifest_path,
            'discovery_status' => $this->discovery_status?->value,
            'operational_status' => $this->operational_status?->value,
            'lifecycle_status' => $this->administrativeLifecycleStatus()->value,
            'discovery_errors' => $this->discovery_errors ?? [],
            'raw_manifest' => $this->raw_manifest ?? [],
            'normalized_manifest' => $this->normalizedManifestSnapshot(),
            'manifest_warnings' => $this->manifest_warnings ?? [],
            'critical' => $this->isCriticalForOperations(),
            'requires' => $this->declaredDependencies(),
            'capabilities' => $this->declaredCapabilities(),
            'permissions' => array_map(
                static fn (PluginPermissionDefinition $permission): array => $permission->toArray(),
                $this->declaredPluginPermissions(),
            ),
            'provider' => $this->declaredProvider(),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
