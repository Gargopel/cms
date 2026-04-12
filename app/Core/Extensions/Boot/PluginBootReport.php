<?php

namespace App\Core\Extensions\Boot;

class PluginBootReport
{
    /**
     * @param  array<int, array<string, mixed>>  $considered
     * @param  array<int, array<string, mixed>>  $registered
     * @param  array<int, array<string, mixed>>  $ignored
     * @param  array<int, array<string, mixed>>  $failed
     * @param  array<int, array<string, mixed>>  $systemErrors
     */
    public function __construct(
        protected array $considered = [],
        protected array $registered = [],
        protected array $ignored = [],
        protected array $failed = [],
        protected array $systemErrors = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'summary' => [
                'considered' => count($this->considered),
                'registered' => count($this->registered),
                'ignored' => count($this->ignored),
                'failed' => count($this->failed),
                'system_errors' => count($this->systemErrors),
            ],
            'considered' => $this->considered,
            'registered' => $this->registered,
            'ignored' => $this->ignored,
            'failed' => $this->failed,
            'system_errors' => $this->systemErrors,
        ];
    }
}
