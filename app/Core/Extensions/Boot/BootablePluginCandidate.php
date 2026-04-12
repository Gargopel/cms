<?php

namespace App\Core\Extensions\Boot;

use App\Core\Extensions\Models\ExtensionRecord;

class BootablePluginCandidate
{
    public function __construct(
        protected ExtensionRecord $record,
        protected string $provider,
    ) {
    }

    public function record(): ExtensionRecord
    {
        return $this->record;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function toArray(): array
    {
        return array_merge($this->record()->toRegistryArray(), [
            'provider' => $this->provider(),
        ]);
    }
}
