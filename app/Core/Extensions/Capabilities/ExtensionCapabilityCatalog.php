<?php

namespace App\Core\Extensions\Capabilities;

class ExtensionCapabilityCatalog
{
    /**
     * @return array<string, string>
     */
    public function definitions(): array
    {
        return [
            'admin_pages' => 'Admin Pages',
            'widgets' => 'Widgets',
            'commands' => 'Commands',
            'migrations' => 'Migrations',
            'providers' => 'Providers',
            'assets' => 'Assets',
            'integrations' => 'Integrations',
            'health_checks' => 'Health Checks',
            'api_routes' => 'API Routes',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recognizedKeys(): array
    {
        return array_keys($this->definitions());
    }

    public function isRecognized(string $capability): bool
    {
        return array_key_exists($capability, $this->definitions());
    }

    public function label(string $capability): string
    {
        return $this->definitions()[$capability] ?? $capability;
    }
}
