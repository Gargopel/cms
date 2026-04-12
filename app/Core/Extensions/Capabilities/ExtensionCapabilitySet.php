<?php

namespace App\Core\Extensions\Capabilities;

class ExtensionCapabilitySet
{
    /**
     * @param  array<int, string>  $recognized
     * @param  array<int, string>  $custom
     */
    public function __construct(
        protected array $recognized = [],
        protected array $custom = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_values(array_unique([
            ...$this->recognized(),
            ...$this->custom(),
        ]));
    }

    /**
     * @return array<int, string>
     */
    public function recognized(): array
    {
        return array_values(array_unique($this->recognized));
    }

    /**
     * @return array<int, string>
     */
    public function custom(): array
    {
        return array_values(array_unique($this->custom));
    }

    public function has(string $capability): bool
    {
        return in_array($capability, $this->all(), true);
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    public function toArray(): array
    {
        return [
            'all' => $this->all(),
            'recognized' => $this->recognized(),
            'custom' => $this->custom(),
        ];
    }
}
