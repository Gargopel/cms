<?php

namespace App\Core\Extensions\Hooks;

class AdminNavigationItem
{
    public function __construct(
        protected string $pluginSlug,
        protected string $key,
        protected string $label,
        protected string $description,
        protected string $href,
        protected ?string $requiredPermission = null,
        protected ?string $activeWhen = null,
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

    public function description(): string
    {
        return $this->description;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function requiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    public function activeWhen(): ?string
    {
        return $this->activeWhen;
    }

    public function uniqueKey(): string
    {
        return $this->pluginSlug.':'.$this->key;
    }

    public function toArray(): array
    {
        return [
            'plugin_slug' => $this->pluginSlug(),
            'key' => $this->key(),
            'label' => $this->label(),
            'description' => $this->description(),
            'href' => $this->href(),
            'required_permission' => $this->requiredPermission(),
            'active_when' => $this->activeWhen(),
        ];
    }
}
