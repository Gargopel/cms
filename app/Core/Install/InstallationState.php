<?php

namespace App\Core\Install;

use Illuminate\Filesystem\Filesystem;

class InstallationState
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    public function isInstalled(): bool
    {
        if ((bool) config('platform.install.force_uninstalled', false)) {
            return false;
        }

        return $this->files->exists($this->markerPath());
    }

    public function markInstalled(array $payload): void
    {
        $directory = dirname($this->markerPath());

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put(
            $this->markerPath(),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    public function details(): ?array
    {
        if (! $this->isInstalled()) {
            return null;
        }

        $decoded = json_decode((string) $this->files->get($this->markerPath()), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function clear(): void
    {
        if ($this->files->exists($this->markerPath())) {
            $this->files->delete($this->markerPath());
        }
    }

    public function markerPath(): string
    {
        return (string) config('platform.install.marker_path', storage_path('app/platform/install/installed.json'));
    }
}
