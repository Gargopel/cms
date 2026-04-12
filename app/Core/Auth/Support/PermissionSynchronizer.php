<?php

namespace App\Core\Auth\Support;

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use Illuminate\Support\Collection;

class PermissionSynchronizer
{
    /**
     * @param  array<int, array{slug: string, name: string, description?: string|null, scope?: string|null}>  $definitions
     * @return Collection<int, Permission>
     */
    public function syncPermissions(array $definitions, string $defaultScope = 'core'): Collection
    {
        return collect($definitions)->map(function (array $definition) use ($defaultScope): Permission {
            $scope = (string) ($definition['scope'] ?? $defaultScope);

            /** @var Permission $permission */
            $permission = Permission::query()->updateOrCreate(
                [
                    'scope' => $scope,
                    'slug' => (string) $definition['slug'],
                ],
                [
                    'name' => (string) $definition['name'],
                    'description' => $definition['description'] ?? null,
                ],
            );

            return $permission;
        });
    }

    /**
     * @param  array{slug: string, name: string, description?: string|null, scope?: string|null}  $definition
     * @param  array<int, Permission>  $permissions
     */
    public function syncRole(array $definition, array $permissions, string $defaultScope = 'core'): Role
    {
        $scope = (string) ($definition['scope'] ?? $defaultScope);

        /** @var Role $role */
        $role = Role::query()->updateOrCreate(
            [
                'scope' => $scope,
                'slug' => (string) $definition['slug'],
            ],
            [
                'name' => (string) $definition['name'],
                'description' => $definition['description'] ?? null,
            ],
        );

        $role->permissions()->sync(collect($permissions)->map(fn (Permission $permission): int => $permission->getKey())->all());

        return $role;
    }
}
