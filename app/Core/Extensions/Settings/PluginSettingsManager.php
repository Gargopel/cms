<?php

namespace App\Core\Extensions\Settings;

use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Settings\Enums\CoreSettingType;
use App\Core\Settings\CoreSettingsManager;
use ValueError;

class PluginSettingsManager
{
    public function __construct(
        protected CoreSettingsManager $settings,
    ) {
    }

    /**
     * @return array<int, PluginSettingsCatalog>
     */
    public function catalogs(): array
    {
        return ExtensionRecord::query()
            ->where('type', ExtensionType::Plugin->value)
            ->orderBy('name')
            ->get()
            ->map(fn (ExtensionRecord $plugin): ?PluginSettingsCatalog => $this->catalogFor($plugin))
            ->filter()
            ->values()
            ->all();
    }

    public function catalogFor(ExtensionRecord|string $plugin): ?PluginSettingsCatalog
    {
        $record = $plugin instanceof ExtensionRecord ? $plugin : $this->findPluginBySlug($plugin);

        if (! $record instanceof ExtensionRecord || ! $this->isEligible($record)) {
            return null;
        }

        $manifest = $record->normalizedManifestSnapshot();
        $settings = $manifest['settings'] ?? null;

        if (! is_array($settings)) {
            return null;
        }

        $fields = $settings['fields'] ?? [];

        if (! is_array($fields) || $fields === []) {
            return null;
        }

        $definitions = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            try {
                $type = CoreSettingType::from((string) $field['type']);
            } catch (ValueError) {
                continue;
            }

            $definitions[] = new PluginSettingDefinition(
                pluginSlug: $record->slug,
                key: (string) $field['key'],
                label: (string) $field['label'],
                description: is_string($field['description'] ?? null) ? $field['description'] : null,
                type: $type,
                input: (string) ($field['input'] ?? 'text'),
                default: $field['default'] ?? null,
            );
        }

        if ($definitions === []) {
            return null;
        }

        $permission = $settings['permission'] ?? null;
        $declaredPermission = is_string($permission) && trim($permission) !== '' ? trim($permission) : null;

        return new PluginSettingsCatalog(
            pluginSlug: $record->slug,
            pluginName: $record->name ?? $record->slug,
            requiredPermission: $declaredPermission,
            fields: $definitions,
            warnings: $this->catalogWarningsFor($record),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function valuesFor(ExtensionRecord|string $plugin): array
    {
        $catalog = $this->catalogFor($plugin);

        if (! $catalog instanceof PluginSettingsCatalog) {
            return [];
        }

        return $this->settings->resolveDefinedGroup($catalog->groupName(), $catalog->fields());
    }

    public function get(ExtensionRecord|string $plugin, string $key, mixed $default = null): mixed
    {
        return $this->valuesFor($plugin)[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeInput(PluginSettingsCatalog $catalog, array $input): array
    {
        $normalized = [];

        foreach ($catalog->fieldDefinitions() as $field) {
            if ($field->type() === CoreSettingType::Boolean) {
                $normalized[$field->key()] = filter_var(
                    $input[$field->key()] ?? false,
                    FILTER_VALIDATE_BOOL,
                    FILTER_NULL_ON_FAILURE,
                ) ?? false;

                continue;
            }

            $normalized[$field->key()] = $input[$field->key()] ?? null;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function update(ExtensionRecord|string $plugin, array $values): array
    {
        $catalog = $this->catalogFor($plugin);

        if (! $catalog instanceof PluginSettingsCatalog) {
            return [];
        }

        return $this->settings->updateDefinedGroup(
            $catalog->groupName(),
            $catalog->fields(),
            $values,
        );
    }

    public function hasSettings(ExtensionRecord|string $plugin): bool
    {
        return $this->catalogFor($plugin) instanceof PluginSettingsCatalog;
    }

    public function isEligible(ExtensionRecord $record): bool
    {
        return $record->type === ExtensionType::Plugin
            && $record->isDiscoveryValid()
            && $record->isAdministrativelyInstalled();
    }

    /**
     * @return array<int, string>
     */
    public function catalogWarningsFor(ExtensionRecord $record): array
    {
        $manifest = $record->normalizedManifestSnapshot();
        $settings = $manifest['settings'] ?? null;

        if (! is_array($settings)) {
            return [];
        }

        $warnings = [];

        if (! is_string($settings['permission'] ?? null) || trim((string) $settings['permission']) === '') {
            $warnings[] = 'This plugin settings catalog does not declare its own permission and currently falls back to manage_extensions.';
        }

        return $warnings;
    }

    protected function findPluginBySlug(string $slug): ?ExtensionRecord
    {
        return ExtensionRecord::query()
            ->where('type', ExtensionType::Plugin->value)
            ->where('slug', $slug)
            ->first();
    }
}
