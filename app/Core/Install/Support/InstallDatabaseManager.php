<?php

namespace App\Core\Install\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallDatabaseManager
{
    public const CONNECTION = 'installer_runtime';

    public function __construct(
        protected InstallDatabaseConfigFactory $configFactory,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function testConnection(array $input): void
    {
        $config = $this->configFactory->make($input);

        $this->prepareSqliteFile($config);
        Config::set('database.connections.'.self::CONNECTION, $config);
        DB::purge(self::CONNECTION);
        DB::connection(self::CONNECTION)->getPdo();
        DB::disconnect(self::CONNECTION);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function apply(array $input): void
    {
        $config = $this->configFactory->make($input);
        $previousDefault = (string) config('database.default');

        $this->prepareSqliteFile($config);

        Config::set('database.connections.'.self::CONNECTION, $config);
        Config::set('database.default', self::CONNECTION);

        DB::purge($previousDefault);
        DB::purge(self::CONNECTION);
        DB::reconnect(self::CONNECTION);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function prepareSqliteFile(array $config): void
    {
        if (($config['driver'] ?? null) !== 'sqlite') {
            return;
        }

        $databasePath = (string) ($config['database'] ?? '');

        if ($databasePath === '' || $databasePath === ':memory:') {
            return;
        }

        $directory = dirname($databasePath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($databasePath)) {
            File::put($databasePath, '');
        }
    }
}
