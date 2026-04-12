<?php

namespace App\Core\Extensions\Boot;

class BootablePluginResolution
{
    /**
     * @param  array<int, BootablePluginCandidate>  $candidates
     * @param  array<int, array<string, mixed>>  $ignored
     * @param  array<int, array<string, mixed>>  $systemErrors
     */
    public function __construct(
        protected array $candidates,
        protected array $ignored,
        protected array $systemErrors = [],
    ) {
    }

    public function candidates(): array
    {
        return $this->candidates;
    }

    public function ignored(): array
    {
        return $this->ignored;
    }

    public function systemErrors(): array
    {
        return $this->systemErrors;
    }
}
