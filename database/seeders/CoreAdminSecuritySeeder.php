<?php

namespace Database\Seeders;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Enums\CoreRole;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoreAdminSecuritySeeder extends Seeder
{
    public function run(): void
    {
        $permissions = app(PermissionSynchronizer::class)->syncPermissions(
            collect(CorePermission::cases())->map(static fn (CorePermission $permission): array => [
                'slug' => $permission->value,
                'name' => $permission->label(),
                'description' => $permission->description(),
            ])->all(),
        );

        app(PermissionSynchronizer::class)->syncRole(
            [
                'slug' => CoreRole::Administrator->value,
                'name' => CoreRole::Administrator->label(),
                'description' => CoreRole::Administrator->description(),
            ],
            $permissions->all(),
        );

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (! (bool) config('platform.admin.seed_local_admin', true)) {
            return;
        }

        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => (string) config('platform.admin.local_admin_email', 'admin@example.test')],
            [
                'name' => (string) config('platform.admin.local_admin_name', 'Local Administrator'),
                'password' => (string) config('platform.admin.local_admin_password', 'admin12345'),
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole(CoreRole::Administrator->value)) {
            /** @var Role $administratorRole */
            $administratorRole = Role::query()
                ->where('scope', 'core')
                ->where('slug', CoreRole::Administrator->value)
                ->firstOrFail();

            $user->roles()->syncWithoutDetaching([$administratorRole->getKey()]);
        }
    }
}
