<?php

namespace App\Core\Settings;

use App\Core\Settings\Enums\CoreSettingType;
use App\Core\Settings\Models\CoreSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CoreSettingsManager
{
    public function __construct(
        protected CoreSettingsCatalog $catalog,
    ) {
    }

    public function get(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $catalogDefault = $this->catalog->defaultValue($group, $key, $default);

        return $this->group($group)[$key] ?? $catalogDefault;
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $resolved = [];

        foreach ($this->catalog->group($group) as $key => $field) {
            $resolved[$key] = $field['default'] ?? null;
        }

        foreach (($this->storedGroups()[$group] ?? []) as $key => $value) {
            $resolved[$key] = $value;
        }

        return $resolved;
    }

    public function put(string $key, mixed $value, CoreSettingType|string $type = CoreSettingType::String, string $group = 'general'): CoreSetting
    {
        $resolvedType = $type instanceof CoreSettingType ? $type : CoreSettingType::from($type);

        /** @var CoreSetting $setting */
        $setting = CoreSetting::query()->updateOrCreate(
            [
                'group_name' => $group,
                'key_name' => $key,
            ],
            [
                'type' => $resolvedType->value,
                'value' => $this->serializeValue($value, $resolvedType),
            ],
        );

        $this->flushCache();

        return $setting;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function updateGroup(string $group, array $values): array
    {
        foreach ($this->catalog->group($group) as $key => $field) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $this->put(
                key: $key,
                value: $values[$key],
                type: $field['setting_type'] ?? CoreSettingType::String,
                group: $group,
            );
        }

        $this->flushCache();

        return $this->group($group);
    }

    public function applyRuntimeConfiguration(): void
    {
        if (! $this->storageIsAvailable()) {
            return;
        }

        $siteName = (string) $this->get('site_name', config('app.name'));
        $locale = (string) $this->get('locale', config('app.locale'));
        $timezone = (string) $this->get('timezone', config('app.timezone'));
        $systemEmail = (string) $this->get('system_email', config('mail.from.address', ''));

        config()->set('app.name', $siteName);
        config()->set('app.locale', $locale);
        config()->set('app.timezone', $timezone);
        config()->set('mail.from.name', $siteName);

        if ($systemEmail !== '') {
            config()->set('mail.from.address', $systemEmail);
        }

        app()->setLocale($locale);
        @date_default_timezone_set($timezone);
    }

    public function flushCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function cacheKey(): string
    {
        return (string) config('platform.settings.cache_key', 'platform.core.settings.cache');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function storedGroups(): array
    {
        if (! $this->storageIsAvailable()) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $resolved */
        $resolved = Cache::rememberForever($this->cacheKey(), function (): array {
            return CoreSetting::query()
                ->orderBy('group_name')
                ->orderBy('key_name')
                ->get()
                ->groupBy('group_name')
                ->map(function ($settings): array {
                    return $settings->mapWithKeys(function (CoreSetting $setting): array {
                        return [
                            $setting->key_name => $this->deserializeValue(
                                $setting->value,
                                CoreSettingType::from((string) $setting->type),
                            ),
                        ];
                    })->all();
                })
                ->all();
        });

        return $resolved;
    }

    protected function storageIsAvailable(): bool
    {
        try {
            return Schema::hasTable('core_settings');
        } catch (Throwable) {
            return false;
        }
    }

    protected function serializeValue(mixed $value, CoreSettingType $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            CoreSettingType::Boolean => $value ? '1' : '0',
            CoreSettingType::String, CoreSettingType::Text => trim((string) $value),
        };
    }

    protected function deserializeValue(?string $value, CoreSettingType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            CoreSettingType::Boolean => $value === '1',
            CoreSettingType::String, CoreSettingType::Text => $value,
        };
    }
}
