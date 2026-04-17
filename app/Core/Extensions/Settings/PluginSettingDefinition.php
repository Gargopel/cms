<?php

namespace App\Core\Extensions\Settings;

use App\Core\Settings\Enums\CoreSettingType;

class PluginSettingDefinition
{
    public function __construct(
        protected string $pluginSlug,
        protected string $key,
        protected string $label,
        protected ?string $description,
        protected CoreSettingType $type,
        protected string $input,
        protected mixed $default = null,
    ) {
    }

    public function pluginSlug(): string
    {
        return $this->pluginSlug;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function type(): CoreSettingType
    {
        return $this->type;
    }

    public function input(): string
    {
        return $this->input;
    }

    public function defaultValue(): mixed
    {
        return $this->default;
    }

    /**
     * @return array{label: string, description: ?string, setting_type: CoreSettingType, input: string, default: mixed}
     */
    public function toFieldDefinition(): array
    {
        return [
            'label' => $this->label(),
            'description' => $this->description(),
            'setting_type' => $this->type(),
            'input' => $this->input(),
            'default' => $this->defaultValue(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        return match ($this->type()) {
            CoreSettingType::String => match ($this->input()) {
                'email' => ['nullable', 'email:rfc', 'max:255'],
                default => ['nullable', 'string', 'max:255'],
            },
            CoreSettingType::Text => ['nullable', 'string', 'max:10000'],
            CoreSettingType::Boolean => ['nullable', 'boolean'],
        };
    }
}
