<?php

namespace App\Core\Extensions\Hooks;

use Closure;

class ThemeSlotBlock
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected string $pluginSlug,
        protected string $key,
        protected string $slot,
        protected string $view,
        protected int $priority = 50,
        protected array $data = [],
        protected ?string $themeView = null,
        protected ?Closure $dataResolver = null,
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

    public function slot(): string
    {
        return $this->slot;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function themeView(): ?string
    {
        return $this->themeView;
    }

    public function dataResolver(): ?Closure
    {
        return $this->dataResolver;
    }

    public function uniqueKey(): string
    {
        return sprintf('%s:%s:%s', $this->pluginSlug, $this->slot, $this->key);
    }
}
