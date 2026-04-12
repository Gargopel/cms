<?php

namespace App\Core\Extensions\Permissions;

class PluginPermissionDefinition
{
    public function __construct(
        protected string $pluginSlug,
        protected string $localSlug,
        protected string $name,
        protected ?string $description = null,
    ) {
    }

    public function pluginSlug(): string
    {
        return $this->pluginSlug;
    }

    public function localSlug(): string
    {
        return $this->localSlug;
    }

    public function slug(): string
    {
        return $this->pluginSlug.'.'.$this->localSlug;
    }

    public function scope(): string
    {
        return 'plugin:'.$this->pluginSlug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return array{scope: string, slug: string, name: string, description: ?string}
     */
    public function toPermissionDefinition(): array
    {
        return [
            'scope' => $this->scope(),
            'slug' => $this->slug(),
            'name' => $this->name(),
            'description' => $this->description(),
        ];
    }

    /**
     * @return array{plugin_slug: string, local_slug: string, slug: string, scope: string, name: string, description: ?string}
     */
    public function toArray(): array
    {
        return [
            'plugin_slug' => $this->pluginSlug(),
            'local_slug' => $this->localSlug(),
            'slug' => $this->slug(),
            'scope' => $this->scope(),
            'name' => $this->name(),
            'description' => $this->description(),
        ];
    }
}
