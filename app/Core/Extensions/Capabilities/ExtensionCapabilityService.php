<?php

namespace App\Core\Extensions\Capabilities;

use App\Core\Extensions\Models\ExtensionRecord;

class ExtensionCapabilityService
{
    public function __construct(
        protected ExtensionCapabilityCatalog $catalog,
    ) {
    }

    public function forExtension(ExtensionRecord $record): ExtensionCapabilitySet
    {
        return $this->forSnapshot($record->normalizedManifestSnapshot());
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     */
    public function forSnapshot(?array $snapshot): ExtensionCapabilitySet
    {
        $capabilities = $snapshot['capabilities'] ?? [];

        if (! is_array($capabilities)) {
            $capabilities = [];
        }

        $recognized = [];
        $custom = [];

        foreach ($capabilities as $capability) {
            if (! is_string($capability) || trim($capability) === '') {
                continue;
            }

            $normalized = trim($capability);

            if ($this->isRecognized($normalized)) {
                $recognized[] = $normalized;
            } else {
                $custom[] = $normalized;
            }
        }

        return new ExtensionCapabilitySet(
            recognized: $recognized,
            custom: $custom,
        );
    }

    /**
     * @return array<int, ExtensionRecord>
     */
    public function extensionsDeclaring(string $capability): array
    {
        $normalized = $this->normalizeKey($capability);

        if ($normalized === null) {
            return [];
        }

        return ExtensionRecord::query()
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('slug')
            ->get()
            ->filter(fn (ExtensionRecord $record): bool => $this->forExtension($record)->has($normalized))
            ->values()
            ->all();
    }

    public function isRecognized(string $capability): bool
    {
        $normalized = $this->normalizeKey($capability);

        return $normalized !== null && $this->catalog->isRecognized($normalized);
    }

    public function label(string $capability): string
    {
        $normalized = $this->normalizeKey($capability);

        return $normalized !== null ? $this->catalog->label($normalized) : $capability;
    }

    /**
     * @return array<int, string>
     */
    public function warningsForExtension(ExtensionRecord $record): array
    {
        return array_values(array_filter(
            $record->manifest_warnings ?? [],
            static fn (mixed $warning): bool => is_string($warning)
                && (str_contains(strtolower($warning), 'capabilit') || str_contains($warning, 'Capability [')),
        ));
    }

    protected function normalizeKey(string $capability): ?string
    {
        $normalized = strtolower(trim($capability));

        return $normalized !== '' ? $normalized : null;
    }
}
