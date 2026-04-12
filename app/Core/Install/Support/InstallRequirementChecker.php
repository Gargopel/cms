<?php

namespace App\Core\Install\Support;

class InstallRequirementChecker
{
    /**
     * @return array{requirements: array<int, array<string, mixed>>, permissions: array<int, array<string, mixed>>, passed: bool}
     */
    public function inspect(): array
    {
        $requirements = [
            $this->requirement('PHP 8.3+', version_compare(PHP_VERSION, (string) config('platform.install.required_php', '8.3.0'), '>='), PHP_VERSION),
            $this->requirement('OpenSSL', extension_loaded('openssl'), extension_loaded('openssl') ? 'enabled' : 'missing'),
            $this->requirement('PDO', extension_loaded('pdo'), extension_loaded('pdo') ? 'enabled' : 'missing'),
            $this->requirement('Mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'enabled' : 'missing'),
            $this->requirement('Tokenizer', extension_loaded('tokenizer'), extension_loaded('tokenizer') ? 'enabled' : 'missing'),
            $this->requirement('JSON', extension_loaded('json'), extension_loaded('json') ? 'enabled' : 'missing'),
            $this->requirement('XML', extension_loaded('xml'), extension_loaded('xml') ? 'enabled' : 'missing'),
        ];

        $permissions = [
            $this->requirement('storage directory writable', is_writable(storage_path()), storage_path()),
            $this->requirement('bootstrap/cache writable', is_writable(base_path('bootstrap/cache')), base_path('bootstrap/cache')),
            $this->requirement('environment target writable', $this->isEnvironmentWritable(), (string) config('platform.install.env_path', base_path('.env'))),
        ];

        $passed = collect($requirements)
            ->merge($permissions)
            ->every(static fn (array $item): bool => (bool) $item['passed']);

        return [
            'requirements' => $requirements,
            'permissions' => $permissions,
            'passed' => $passed,
        ];
    }

    protected function isEnvironmentWritable(): bool
    {
        $path = (string) config('platform.install.env_path', base_path('.env'));

        if (file_exists($path)) {
            return is_writable($path);
        }

        return is_writable(dirname($path));
    }

    protected function requirement(string $label, bool $passed, string $details): array
    {
        return [
            'label' => $label,
            'passed' => $passed,
            'details' => $details,
        ];
    }
}
