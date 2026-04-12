<?php

namespace App\Core\Auth\Support;

use App\Core\Auth\Enums\CoreRole;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SecurityGovernanceService
{
    /**
     * @param  array<int, int|string>  $roleIds
     */
    public function syncUserRoles(User $actor, User $target, array $roleIds): void
    {
        $submittedRoleIds = collect($roleIds)
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        /** @var Collection<int, Role> $roles */
        $roles = Role::query()
            ->whereIn('id', $submittedRoleIds->all())
            ->get();

        $assigningSuperAdministrator = $roles->contains(
            static fn (Role $role): bool => $role->slug === CoreRole::Administrator->value
        );

        if ($assigningSuperAdministrator && ! $actor->hasRole(CoreRole::Administrator->value)) {
            throw ValidationException::withMessages([
                'role_ids' => 'Apenas um super administrador pode atribuir o cargo administrativo principal do core.',
            ]);
        }

        $currentRoleIds = $target->roles()->pluck('roles.id');

        if (
            $actor->is($target)
            && ! $actor->hasRole(CoreRole::Administrator->value)
            && $currentRoleIds->sort()->values()->all() !== $submittedRoleIds->sort()->values()->all()
        ) {
            throw ValidationException::withMessages([
                'role_ids' => 'Voce nao pode alterar os seus proprios cargos sem privilegio de super administrador.',
            ]);
        }

        $targetCurrentlyIsSuperAdministrator = $target->hasRole(CoreRole::Administrator->value);
        $targetWillRemainSuperAdministrator = $assigningSuperAdministrator;

        if (
            $targetCurrentlyIsSuperAdministrator
            && ! $targetWillRemainSuperAdministrator
            && $this->superAdministratorCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'role_ids' => 'Nao e permitido remover o ultimo super administrador do sistema.',
            ]);
        }

        $target->roles()->sync($roles->pluck('id')->all());
    }

    /**
     * @param  array<int, int|string>  $permissionIds
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $submittedPermissionIds = collect($permissionIds)
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $permissions = Permission::query()
            ->whereIn('id', $submittedPermissionIds->all())
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissions);
    }

    public function superAdministratorCount(): int
    {
        return User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('slug', CoreRole::Administrator->value);
            })
            ->count();
    }
}
