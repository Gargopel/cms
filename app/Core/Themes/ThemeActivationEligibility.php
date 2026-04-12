<?php

namespace App\Core\Themes;

class ThemeActivationEligibility
{
    /**
     * @param  array<int, array{code: string, message: string}>  $blocks
     * @param  array<int, array{code: string, message: string}>  $warnings
     */
    public function __construct(
        protected bool $allowed,
        protected string $message,
        protected array $blocks = [],
        protected array $warnings = [],
    ) {
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array<int, array{code: string, message: string}>
     */
    public function blocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return array<int, array{code: string, message: string}>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function primaryBlockMessage(): ?string
    {
        return $this->blocks[0]['message'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed(),
            'message' => $this->message(),
            'blocks' => $this->blocks(),
            'warnings' => $this->warnings(),
        ];
    }
}
