<?php

namespace App\Core\Extensions\Hooks;

class AdminDashboardPanel
{
    public function __construct(
        protected string $pluginSlug,
        protected string $key,
        protected string $title,
        protected string $description,
        protected ?string $href = null,
        protected ?string $requiredPermission = null,
        protected ?string $badge = null,
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

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function href(): ?string
    {
        return $this->href;
    }

    public function requiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    public function badge(): ?string
    {
        return $this->badge;
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
            'title' => $this->title(),
            'description' => $this->description(),
            'href' => $this->href(),
            'required_permission' => $this->requiredPermission(),
            'badge' => $this->badge(),
        ];
    }
}
