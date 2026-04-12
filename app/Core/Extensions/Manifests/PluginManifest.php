<?php

namespace App\Core\Extensions\Manifests;

use App\Core\Extensions\Enums\ExtensionType;
use Illuminate\Support\Arr;

class PluginManifest extends AbstractExtensionManifest
{
    public function __construct(
        string $name,
        string $slug,
        string $description,
        string $version,
        string $author,
        ?string $vendor,
        string $minCoreVersion,
        ?string $maxCoreVersion,
        string $path,
        string $manifestPath,
        protected ?string $provider = null,
        bool $critical = false,
        array $requires = [],
        array $capabilities = [],
        array $permissions = [],
        array $extra = [],
    ) {
        parent::__construct(
            name: $name,
            slug: $slug,
            description: $description,
            version: $version,
            author: $author,
            vendor: $vendor,
            minCoreVersion: $minCoreVersion,
            maxCoreVersion: $maxCoreVersion,
            path: $path,
            manifestPath: $manifestPath,
            provider: $provider,
            critical: $critical,
            requires: $requires,
            capabilities: $capabilities,
            permissions: $permissions,
            extra: $extra,
        );
    }

    public static function fromArray(array $data, string $path, string $manifestPath): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            version: $data['version'],
            author: $data['author'],
            vendor: $data['vendor'] ?? null,
            minCoreVersion: $data['core']['min'],
            maxCoreVersion: $data['core']['max'] ?? null,
            path: $path,
            manifestPath: $manifestPath,
            provider: $data['provider'] ?? null,
            critical: (bool) ($data['critical'] ?? false),
            requires: $data['requires'] ?? [],
            capabilities: $data['capabilities'] ?? [],
            permissions: $data['permissions'] ?? [],
            extra: Arr::except($data, ['name', 'slug', 'description', 'version', 'author', 'vendor', 'core', 'provider', 'critical', 'requires', 'capabilities', 'permissions']),
        );
    }

    public function type(): ExtensionType
    {
        return ExtensionType::Plugin;
    }
}
