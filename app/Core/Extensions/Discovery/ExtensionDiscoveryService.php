<?php

namespace App\Core\Extensions\Discovery;

use App\Core\Contracts\Extensions\ExtensionManifest;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Manifests\PluginManifest;
use App\Core\Extensions\Manifests\ThemeManifest;
use App\Core\Extensions\Validation\ExtensionManifestNormalizer;
use App\Core\Extensions\Validation\ExtensionManifestValidator;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use JsonException;
use Throwable;

class ExtensionDiscoveryService
{
    public function __construct(
        protected Filesystem $files,
        protected PlatformPaths $paths,
        protected ExtensionManifestValidator $validator,
        protected ExtensionManifestNormalizer $normalizer,
    ) {
    }

    public function discover(): ExtensionDiscoveryResult
    {
        return new ExtensionDiscoveryResult(
            coreVersion: $this->coreVersion(),
            plugins: $this->discoverPlugins(),
            themes: $this->discoverThemes(),
        );
    }

    public function discoverPlugins(?string $rootPath = null): array
    {
        return $this->discoverByType(
            type: ExtensionType::Plugin,
            rootPath: $rootPath ?? $this->pluginsRootPath(),
        );
    }

    public function discoverThemes(?string $rootPath = null): array
    {
        return $this->discoverByType(
            type: ExtensionType::Theme,
            rootPath: $rootPath ?? $this->themesRootPath(),
        );
    }

    protected function discoverByType(ExtensionType $type, string $rootPath): array
    {
        if (! $this->files->isDirectory($rootPath)) {
            return [];
        }

        $directories = $this->files->directories($rootPath);
        sort($directories);

        return array_map(
            fn (string $directory): DiscoveredExtension => $this->discoverDirectory($directory, $type),
            $directories
        );
    }

    protected function discoverDirectory(string $directory, ExtensionType $type): DiscoveredExtension
    {
        $manifestPath = $directory.DIRECTORY_SEPARATOR.$this->manifestFileName($type);
        $directoryName = basename($directory);

        if (! $this->files->exists($manifestPath)) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: null,
                errors: [sprintf('Manifest file [%s] was not found.', $this->manifestFileName($type))],
            );
        }

        try {
            $payload = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                errors: [sprintf('Manifest JSON is invalid: %s', $exception->getMessage())],
            );
        } catch (Throwable $exception) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                errors: [sprintf('Manifest could not be read safely: %s', $exception->getMessage())],
            );
        }

        if (! is_array($payload)) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                errors: ['Manifest root must decode to an object.'],
            );
        }

        $normalized = $this->normalizer->normalize($payload, $type);
        $errors = $this->validator->validate($normalized->normalized(), $type);
        $warnings = $normalized->warnings();
        $normalizedPayload = $normalized->normalized();

        if ($errors !== []) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                errors: $errors,
                warnings: $warnings,
                normalizedManifest: $normalizedPayload,
                rawManifest: $payload,
            );
        }

        try {
            $manifest = $this->makeManifest($type, $normalizedPayload, $directory, $manifestPath);
        } catch (Throwable $exception) {
            return DiscoveredExtension::invalid(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                errors: [sprintf('Manifest could not be hydrated into a contract object: %s', $exception->getMessage())],
                warnings: $warnings,
                normalizedManifest: $normalizedPayload,
                rawManifest: $payload,
            );
        }

        $compatibilityErrors = $this->validateCompatibility($manifest);

        if ($compatibilityErrors !== []) {
            return DiscoveredExtension::incompatible(
                type: $type,
                directory: $directoryName,
                path: $directory,
                manifestPath: $manifestPath,
                manifest: $manifest,
                errors: $compatibilityErrors,
                warnings: $warnings,
                normalizedManifest: $normalizedPayload,
                rawManifest: $payload,
            );
        }

        return DiscoveredExtension::valid(
            type: $type,
            directory: $directoryName,
            path: $directory,
            manifestPath: $manifestPath,
            manifest: $manifest,
            warnings: $warnings,
            normalizedManifest: $normalizedPayload,
            rawManifest: $payload,
        );
    }

    protected function makeManifest(
        ExtensionType $type,
        array $payload,
        string $directory,
        string $manifestPath,
    ): ExtensionManifest {
        return match ($type) {
            ExtensionType::Plugin => PluginManifest::fromArray($payload, $directory, $manifestPath),
            ExtensionType::Theme => ThemeManifest::fromArray($payload, $directory, $manifestPath),
        };
    }

    protected function validateCompatibility(ExtensionManifest $manifest): array
    {
        $coreVersion = $this->coreVersion();

        if (! $this->isValidVersion($coreVersion)) {
            return [sprintf('Current core version [%s] is invalid.', $coreVersion)];
        }

        if (version_compare($coreVersion, $manifest->minCoreVersion(), '<')) {
            return [sprintf(
                'Extension requires core version >= [%s], current version is [%s].',
                $manifest->minCoreVersion(),
                $coreVersion,
            )];
        }

        if ($manifest->maxCoreVersion() !== null && version_compare($coreVersion, $manifest->maxCoreVersion(), '>')) {
            return [sprintf(
                'Extension supports core version up to [%s], current version is [%s].',
                $manifest->maxCoreVersion(),
                $coreVersion,
            )];
        }

        return [];
    }

    protected function manifestFileName(ExtensionType $type): string
    {
        return match ($type) {
            ExtensionType::Plugin => (string) config('platform.extensions.plugin_manifest', 'plugin.json'),
            ExtensionType::Theme => (string) config('platform.extensions.theme_manifest', 'theme.json'),
        };
    }

    protected function coreVersion(): string
    {
        return (string) config('platform.core.version', '0.1.0');
    }

    protected function pluginsRootPath(): string
    {
        return $this->paths->base((string) config('platform.extensions.plugins_path', 'plugins'));
    }

    protected function themesRootPath(): string
    {
        return $this->paths->base((string) config('platform.extensions.themes_path', 'themes'));
    }

    protected function isValidVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/', $version) === 1;
    }
}
