<?php

namespace App\Core\Extensions\Manifests;

use App\Core\Extensions\Enums\ExtensionType;
use Illuminate\Support\Arr;

class ThemeManifest extends AbstractExtensionManifest
{
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
            provider: null,
            critical: (bool) ($data['critical'] ?? false),
            requires: $data['requires'] ?? [],
            capabilities: $data['capabilities'] ?? [],
            permissions: [],
            extra: Arr::except($data, ['name', 'slug', 'description', 'version', 'author', 'vendor', 'core', 'critical', 'requires', 'capabilities', 'permissions']),
        );
    }

    public function type(): ExtensionType
    {
        return ExtensionType::Theme;
    }
}
