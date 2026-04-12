<?php

namespace App\Core\Install\Setup;

use App\Core\Auth\Enums\CoreRole;
use App\Core\Auth\Models\Role;
use App\Core\Install\Environment\EnvironmentFileManager;
use App\Core\Install\InstallationState;
use App\Core\Install\Support\InstallDatabaseManager;
use App\Core\Install\Support\InstallRequirementChecker;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Throwable;

class InstallApplication
{
    public function __construct(
        protected InstallRequirementChecker $requirementChecker,
        protected InstallDatabaseManager $databaseManager,
        protected EnvironmentFileManager $environmentFileManager,
        protected InstallationState $installationState,
    ) {
    }

    /**
     * @param  array<string, mixed>  $database
     * @param  array<string, mixed>  $administrator
     * @return array<string, mixed>
     */
    public function run(array $database, array $administrator): array
    {
        $requirements = $this->requirementChecker->inspect();

        if (! $requirements['passed']) {
            throw new InstallException('O ambiente nao atende aos requisitos minimos para concluir a instalacao.');
        }

        $this->databaseManager->testConnection($database);

        $appKey = (string) config('app.key');

        if ($appKey === '') {
            $appKey = 'base64:'.base64_encode(random_bytes(32));
        }

        $this->environmentFileManager->write($this->environmentPayload($database, $administrator, $appKey));
        $this->applyRuntimeConfiguration($database, $administrator, $appKey);

        Config::set('platform.admin.seed_local_admin', false);

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\CoreAdminSecuritySeeder::class, '--force' => true]);

        $admin = $this->createInitialAdministrator($administrator);

        $payload = [
            'installed_at' => now()->toIso8601String(),
            'core_version' => (string) config('platform.core.version', '0.1.0'),
            'app_url' => (string) config('app.url'),
            'admin_email' => $admin->email,
        ];

        $this->installationState->markInstalled($payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $database
     * @param  array<string, mixed>  $administrator
     * @return array<string, scalar|null>
     */
    protected function environmentPayload(array $database, array $administrator, string $appKey): array
    {
        $driver = (string) $database['driver'];

        return [
            'APP_NAME' => (string) ($administrator['app_name'] ?? config('platform.core.name', 'CMS Platform Core')),
            'APP_URL' => (string) ($administrator['app_url'] ?? config('app.url')),
            'APP_KEY' => $appKey,
            'APP_INSTALLED' => 'true',
            'DB_CONNECTION' => $driver,
            'DB_HOST' => $driver === 'sqlite' ? null : (string) ($database['host'] ?? ''),
            'DB_PORT' => $driver === 'sqlite' ? null : (string) ($database['port'] ?? ''),
            'DB_DATABASE' => (string) ($database['database'] ?? ''),
            'DB_USERNAME' => $driver === 'sqlite' ? null : (string) ($database['username'] ?? ''),
            'DB_PASSWORD' => $driver === 'sqlite' ? null : (string) ($database['password'] ?? ''),
            'CORE_ADMIN_SEED_LOCAL' => 'false',
        ];
    }

    /**
     * @param  array<string, mixed>  $database
     * @param  array<string, mixed>  $administrator
     */
    protected function applyRuntimeConfiguration(array $database, array $administrator, string $appKey): void
    {
        Config::set('app.key', $appKey);
        Config::set('app.name', (string) ($administrator['app_name'] ?? config('platform.core.name')));
        Config::set('app.url', (string) ($administrator['app_url'] ?? config('app.url')));

        $this->databaseManager->apply($database);
    }

    /**
     * @param  array<string, mixed>  $administrator
     */
    protected function createInitialAdministrator(array $administrator): User
    {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => (string) $administrator['email']],
            [
                'name' => (string) $administrator['name'],
                'password' => (string) $administrator['password'],
                'email_verified_at' => now(),
            ],
        );

        /** @var Role $role */
        $role = Role::query()
            ->where('scope', 'core')
            ->where('slug', CoreRole::Administrator->value)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->getKey()]);

        return $user;
    }
}
