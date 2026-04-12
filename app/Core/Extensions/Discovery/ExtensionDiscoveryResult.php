<?php

namespace App\Core\Extensions\Discovery;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;

class ExtensionDiscoveryResult
{
    /**
     * @param  array<int, DiscoveredExtension>  $plugins
     * @param  array<int, DiscoveredExtension>  $themes
     */
    public function __construct(
        protected string $coreVersion,
        protected array $plugins,
        protected array $themes,
    ) {
    }

    public function coreVersion(): string
    {
        return $this->coreVersion;
    }

    public function plugins(): array
    {
        return $this->plugins;
    }

    public function themes(): array
    {
        return $this->themes;
    }

    public function all(): array
    {
        return array_merge($this->plugins(), $this->themes());
    }

    public function usablePlugins(): array
    {
        return array_values(array_filter($this->plugins(), static fn (DiscoveredExtension $extension): bool => $extension->isUsable()));
    }

    public function usableThemes(): array
    {
        return array_values(array_filter($this->themes(), static fn (DiscoveredExtension $extension): bool => $extension->isUsable()));
    }

    public function summary(): array
    {
        return [
            'plugins' => $this->summarize($this->plugins()),
            'themes' => $this->summarize($this->themes()),
            'total' => count($this->all()),
            'usable' => count($this->usablePlugins()) + count($this->usableThemes()),
        ];
    }

    public function toArray(): array
    {
        return [
            'core_version' => $this->coreVersion(),
            'summary' => $this->summary(),
            'plugins' => array_map(static fn (DiscoveredExtension $extension): array => $extension->toArray(), $this->plugins()),
            'themes' => array_map(static fn (DiscoveredExtension $extension): array => $extension->toArray(), $this->themes()),
        ];
    }

    /**
     * @param  array<int, DiscoveredExtension>  $extensions
     */
    protected function summarize(array $extensions): array
    {
        return [
            'total' => count($extensions),
            'valid' => $this->countByStatus($extensions, ExtensionDiscoveryStatus::Valid),
            'invalid' => $this->countByStatus($extensions, ExtensionDiscoveryStatus::Invalid),
            'incompatible' => $this->countByStatus($extensions, ExtensionDiscoveryStatus::Incompatible),
        ];
    }

    /**
     * @param  array<int, DiscoveredExtension>  $extensions
     */
    protected function countByStatus(array $extensions, ExtensionDiscoveryStatus $status): int
    {
        return count(array_filter(
            $extensions,
            static fn (DiscoveredExtension $extension): bool => $extension->status() === $status
        ));
    }
}
