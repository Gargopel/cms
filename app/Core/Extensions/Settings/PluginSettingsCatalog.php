<?php

namespace App\Core\Extensions\Settings;

use App\Core\Auth\Enums\CorePermission;

class PluginSettingsCatalog
{
    /**
     * @param  array<int, PluginSettingDefinition>  $fields
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        protected string $pluginSlug,
        protected string $pluginName,
        protected ?string $requiredPermission,
        protected array $fields,
        protected array $warnings = [],
    ) {
    }

    public function pluginSlug(): string
    {
        return $this->pluginSlug;
    }

    public function pluginName(): string
    {
        return $this->pluginName;
    }

    public function groupName(): string
    {
        return 'plugin:'.$this->pluginSlug();
    }

    public function declaredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    public function resolvedPermission(): string
    {
        if ($this->requiredPermission === null) {
            return CorePermission::ManageExtensions->value;
        }

        return str_contains($this->requiredPermission, '.')
            ? $this->requiredPermission
            : $this->pluginSlug().'.'.$this->requiredPermission;
    }

    public function usesFallbackPermission(): bool
    {
        return $this->requiredPermission === null;
    }

    /**
     * @return array<int, PluginSettingDefinition>
     */
    public function fieldDefinitions(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, array{label: string, description: ?string, setting_type: \App\Core\Settings\Enums\CoreSettingType, input: string, default: mixed}>
     */
    public function fields(): array
    {
        $resolved = [];

        foreach ($this->fieldDefinitions() as $field) {
            $resolved[$field->key()] = $field->toFieldDefinition();
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach ($this->fieldDefinitions() as $field) {
            $defaults[$field->key()] = $field->defaultValue();
        }

        return $defaults;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->fieldDefinitions() as $field) {
            $rules[$field->key()] = $field->validationRules();
        }

        return $rules;
    }

    public function hasFields(): bool
    {
        return $this->fields !== [];
    }

    /**
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
