<?php

namespace App\Core\Install\Support;

class InstallDatabaseConfigFactory
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function make(array $input): array
    {
        $driver = (string) ($input['driver'] ?? 'mysql');

        return match ($driver) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => $this->normalizeSqlitePath((string) ($input['database'] ?? database_path('database.sqlite'))),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => (string) ($input['host'] ?? '127.0.0.1'),
                'port' => (string) ($input['port'] ?? '5432'),
                'database' => (string) ($input['database'] ?? ''),
                'username' => (string) ($input['username'] ?? ''),
                'password' => (string) ($input['password'] ?? ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            default => [
                'driver' => $driver,
                'host' => (string) ($input['host'] ?? '127.0.0.1'),
                'port' => (string) ($input['port'] ?? '3306'),
                'database' => (string) ($input['database'] ?? ''),
                'username' => (string) ($input['username'] ?? ''),
                'password' => (string) ($input['password'] ?? ''),
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        };
    }

    public function normalizeSqlitePath(string $path): string
    {
        if ($path === ':memory:') {
            return $path;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
