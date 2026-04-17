<?php

namespace App\Core\Extensions\Manifests;

use App\Core\Contracts\Extensions\ExtensionManifest;
use App\Core\Extensions\Enums\ExtensionType;

abstract class AbstractExtensionManifest implements ExtensionManifest
{
    public function __construct(
        protected string $name,
        protected string $slug,
        protected string $description,
        protected string $version,
        protected string $author,
        protected ?string $vendor,
        protected string $minCoreVersion,
        protected ?string $maxCoreVersion,
        protected string $path,
        protected string $manifestPath,
        protected ?string $provider = null,
        protected bool $critical = false,
        protected array $requires = [],
        protected array $capabilities = [],
        protected array $permissions = [],
        protected array $settings = [],
        protected array $extra = [],
    ) {
    }

    abstract public function type(): ExtensionType;

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function author(): string
    {
        return $this->author;
    }

    public function vendor(): ?string
    {
        return $this->vendor;
    }

    public function minCoreVersion(): string
    {
        return $this->minCoreVersion;
    }

    public function maxCoreVersion(): ?string
    {
        return $this->maxCoreVersion;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function manifestPath(): string
    {
        return $this->manifestPath;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function critical(): bool
    {
        return $this->critical;
    }

    public function requires(): array
    {
        return $this->requires;
    }

    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function settings(): array
    {
        return $this->settings;
    }

    public function extra(): array
    {
        return $this->extra;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type()->value,
            'name' => $this->name(),
            'slug' => $this->slug(),
            'description' => $this->description(),
            'version' => $this->version(),
            'author' => $this->author(),
            'vendor' => $this->vendor(),
            'core' => array_filter([
                'min' => $this->minCoreVersion(),
                'max' => $this->maxCoreVersion(),
            ], static fn ($value): bool => $value !== null),
            'provider' => $this->provider(),
            'critical' => $this->critical(),
            'requires' => $this->requires(),
            'capabilities' => $this->capabilities(),
            'permissions' => $this->permissions(),
            'settings' => $this->settings(),
            'path' => $this->path(),
            'manifest_path' => $this->manifestPath(),
            'extra' => $this->extra(),
        ];
    }
}
