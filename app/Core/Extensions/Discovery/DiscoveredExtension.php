<?php

namespace App\Core\Extensions\Discovery;

use App\Core\Contracts\Extensions\ExtensionManifest;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionType;

class DiscoveredExtension
{
    public function __construct(
        protected ExtensionType $type,
        protected string $directory,
        protected string $path,
        protected ?string $manifestPath,
        protected ExtensionDiscoveryStatus $status,
        protected ?ExtensionManifest $manifest = null,
        protected array $errors = [],
        protected array $warnings = [],
        protected ?array $normalizedManifest = null,
        protected array $rawManifest = [],
    ) {
    }

    public static function valid(
        ExtensionType $type,
        string $directory,
        string $path,
        string $manifestPath,
        ExtensionManifest $manifest,
        array $warnings = [],
        ?array $normalizedManifest = null,
        array $rawManifest = [],
    ): self {
        return new self(
            type: $type,
            directory: $directory,
            path: $path,
            manifestPath: $manifestPath,
            status: ExtensionDiscoveryStatus::Valid,
            manifest: $manifest,
            errors: [],
            warnings: $warnings,
            normalizedManifest: $normalizedManifest ?? $manifest->toArray(),
            rawManifest: $rawManifest,
        );
    }

    public static function invalid(
        ExtensionType $type,
        string $directory,
        string $path,
        ?string $manifestPath,
        array $errors,
        array $warnings = [],
        ?array $normalizedManifest = null,
        array $rawManifest = [],
    ): self {
        return new self(
            type: $type,
            directory: $directory,
            path: $path,
            manifestPath: $manifestPath,
            status: ExtensionDiscoveryStatus::Invalid,
            manifest: null,
            errors: $errors,
            warnings: $warnings,
            normalizedManifest: $normalizedManifest,
            rawManifest: $rawManifest,
        );
    }

    public static function incompatible(
        ExtensionType $type,
        string $directory,
        string $path,
        string $manifestPath,
        ExtensionManifest $manifest,
        array $errors,
        array $warnings = [],
        ?array $normalizedManifest = null,
        array $rawManifest = [],
    ): self {
        return new self(
            type: $type,
            directory: $directory,
            path: $path,
            manifestPath: $manifestPath,
            status: ExtensionDiscoveryStatus::Incompatible,
            manifest: $manifest,
            errors: $errors,
            warnings: $warnings,
            normalizedManifest: $normalizedManifest ?? $manifest->toArray(),
            rawManifest: $rawManifest,
        );
    }

    public function type(): ExtensionType
    {
        return $this->type;
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function manifestPath(): ?string
    {
        return $this->manifestPath;
    }

    public function status(): ExtensionDiscoveryStatus
    {
        return $this->status;
    }

    public function manifest(): ?ExtensionManifest
    {
        return $this->manifest;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    public function normalizedManifest(): ?array
    {
        return $this->normalizedManifest;
    }

    public function rawManifest(): array
    {
        return $this->rawManifest;
    }

    public function isUsable(): bool
    {
        return $this->status === ExtensionDiscoveryStatus::Valid;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type()->value,
            'directory' => $this->directory(),
            'path' => $this->path(),
            'manifest_path' => $this->manifestPath(),
            'status' => $this->status()->value,
            'is_usable' => $this->isUsable(),
            'errors' => $this->errors(),
            'warnings' => $this->warnings(),
            'manifest' => $this->manifest()?->toArray(),
            'normalized_manifest' => $this->normalizedManifest(),
            'raw_manifest' => $this->rawManifest(),
        ];
    }
}
