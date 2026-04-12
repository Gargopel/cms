<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Http\Requests\StoreRoleRequest;
use App\Core\Admin\Http\Requests\UpdateRoleRequest;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\SecurityGovernanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminRolesController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'pageTitle' => 'Roles',
            'pageSubtitle' => 'Governanca operacional de cargos do core, incluindo atribuicao de permissoes e leitura de impacto.',
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('scope')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', [
            'pageTitle' => 'Create Role',
            'pageSubtitle' => 'Crie um cargo do core com slug previsivel e, se permitido, atribua permissoes.',
            'role' => new Role(['scope' => 'core']),
            'permissions' => $this->availablePermissions(),
            'assignedPermissionIds' => [],
            'canManagePermissions' => request()->user()?->can('manage_permissions') ?? false,
            'formAction' => route('admin.roles.store'),
            'formMethod' => 'POST',
            'isEditing' => false,
        ]);
    }

    public function store(StoreRoleRequest $request, SecurityGovernanceService $security, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $role = Role::query()->create([
            'scope' => 'core',
            'slug' => (string) $request->string('slug'),
            'name' => (string) $request->string('name'),
            'description' => $request->validated('description'),
        ]);

        if ($request->user()?->can('manage_permissions')) {
            $security->syncRolePermissions($role, $request->validated('permission_ids', []));
        }

        $auditLogger->log(
            action: 'admin.role.created',
            actor: $request->user(),
            target: $role,
            summary: 'Created a core role.',
            metadata: [
                'role_slug' => $role->slug,
                'permission_slugs' => $role->permissions()->orderBy('slug')->pluck('slug')->all(),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', [
            'pageTitle' => 'Edit Role',
            'pageSubtitle' => 'Atualize os metadados do cargo e gerencie as permissoes permitidas para ele.',
            'role' => $role->load('permissions'),
            'permissions' => $this->availablePermissions(),
            'assignedPermissionIds' => $role->permissions->pluck('id')->all(),
            'canManagePermissions' => request()->user()?->can('manage_permissions') ?? false,
            'formAction' => route('admin.roles.update', $role),
            'formMethod' => 'PUT',
            'isEditing' => true,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, SecurityGovernanceService $security, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $role->update([
            'name' => (string) $request->string('name'),
            'description' => $request->validated('description'),
        ]);

        if ($request->user()?->can('manage_permissions')) {
            $security->syncRolePermissions($role, $request->validated('permission_ids', []));
        }

        $auditLogger->log(
            action: 'admin.role.updated',
            actor: $request->user(),
            target: $role,
            summary: 'Updated a core role.',
            metadata: [
                'role_slug' => $role->slug,
                'permission_slugs' => $role->permissions()->orderBy('slug')->pluck('slug')->all(),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated successfully.');
    }

    protected function availablePermissions()
    {
        return Permission::query()
            ->withCount('roles')
            ->orderBy('scope')
            ->orderBy('name')
            ->get()
            ->groupBy('scope');
    }
}
