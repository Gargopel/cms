<?php

namespace App\Core\Settings;

use App\Core\Settings\Enums\CoreSettingType;

class CoreSettingsCatalog
{
    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function groups(): array
    {
        return [
            'general' => [
                'site_name' => [
                    'label' => 'Site Name',
                    'description' => 'Nome principal da instancia usada pelo CMS e por telas publicas futuras.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'text',
                    'default' => (string) config('app.name', config('platform.core.name', 'CMS Platform Core')),
                ],
                'site_tagline' => [
                    'label' => 'Tagline',
                    'description' => 'Descricao curta para identificar a proposta do site ou operacao.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'text',
                    'default' => '',
                ],
                'system_email' => [
                    'label' => 'System Email',
                    'description' => 'Email base do sistema para notificacoes e contatos operacionais do core.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'email',
                    'default' => (string) config('mail.from.address', ''),
                ],
                'timezone' => [
                    'label' => 'Timezone',
                    'description' => 'Timezone padrao do runtime da aplicacao.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'text',
                    'default' => (string) config('app.timezone', 'UTC'),
                ],
                'locale' => [
                    'label' => 'Locale',
                    'description' => 'Locale padrao usado pelo core para datas, traducao e convencoes futuras.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'text',
                    'default' => (string) config('app.locale', 'en'),
                ],
                'footer_text' => [
                    'label' => 'Footer Text',
                    'description' => 'Texto base para o rodape publico ou institucional quando o frontend do produto crescer.',
                    'setting_type' => CoreSettingType::Text,
                    'input' => 'textarea',
                    'default' => '',
                ],
                'global_scripts' => [
                    'label' => 'Global Scripts',
                    'description' => 'Scripts globais opcionais armazenados para uso futuro controlado. O core ainda nao os injeta automaticamente.',
                    'setting_type' => CoreSettingType::Text,
                    'input' => 'textarea',
                    'default' => '',
                ],
            ],
            'themes' => [
                'active_theme_slug' => [
                    'label' => 'Active Theme Slug',
                    'description' => 'Slug tecnico do tema ativo da instancia. Gerenciado pelo theme manager do core.',
                    'setting_type' => CoreSettingType::String,
                    'input' => 'hidden',
                    'default' => null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function group(string $group): array
    {
        return $this->groups()[$group] ?? [];
    }

    public function defaultValue(string $group, string $key, mixed $fallback = null): mixed
    {
        return $this->group($group)[$key]['default'] ?? $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    public function field(string $group, string $key): array
    {
        return $this->group($group)[$key] ?? [];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(string $group): array
    {
        if ($group !== 'general') {
            return [];
        }

        return [
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'system_email' => ['required', 'email:rfc', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', 'string', 'max:12', 'regex:/^[a-z]{2}(?:[_-][A-Z]{2})?$/'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            'global_scripts' => ['nullable', 'string', 'max:20000', 'not_regex:/<\\?(?:php|=)?/i'],
        ];
    }
}
