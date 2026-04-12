<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Http\Requests\StoreAdminUserRequest;
use App\Core\Admin\Http\Requests\UpdateAdminUserRequest;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\SecurityGovernanceService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUsersController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'pageTitle' => 'Users',
            'pageSubtitle' => 'Governanca de usuarios do core com leitura rapida de acessos e cargos atribuidos.',
            'users' => User::query()->with('roles')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'pageTitle' => 'Create User',
            'pageSubtitle' => 'Crie um usuario do core e, se permitido, atribua cargos administrativos.',
            'user' => new User(),
            'roles' => $this->availableRoles(),
            'assignedRoleIds' => [],
            'canManageRoles' => request()->user()?->can('manage_roles') ?? false,
            'formAction' => route('admin.users.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(StoreAdminUserRequest $request, SecurityGovernanceService $security, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $user = User::query()->create([
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
            'password' => Hash::make((string) $request->string('password')),
        ]);

        if ($request->user()?->can('manage_roles')) {
            $security->syncUserRoles($request->user(), $user, $request->validated('role_ids', []));
        }

        $auditLogger->log(
            action: 'admin.user.created',
            actor: $request->user(),
            target: $user,
            summary: 'Created an administrative user.',
            metadata: [
                'email' => $user->email,
                'role_slugs' => $user->roles()->orderBy('slug')->pluck('slug')->all(),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'pageTitle' => 'Edit User',
            'pageSubtitle' => 'Atualize dados do usuario e ajuste cargos quando a sua permissao permitir.',
            'user' => $user->load('roles'),
            'roles' => $this->availableRoles(),
            'assignedRoleIds' => $user->roles->pluck('id')->all(),
            'canManageRoles' => request()->user()?->can('manage_roles') ?? false,
            'formAction' => route('admin.users.update', $user),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $user, SecurityGovernanceService $security, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $payload = [
            'name' => (string) $request->string('name'),
            'email' => (string) $request->string('email'),
        ];

        if ($request->filled('password')) {
            $payload['password'] = Hash::make((string) $request->string('password'));
        }

        $user->update($payload);

        if ($request->user()?->can('manage_roles')) {
            $security->syncUserRoles($request->user(), $user, $request->validated('role_ids', []));
        }

        $auditLogger->log(
            action: 'admin.user.updated',
            actor: $request->user(),
            target: $user,
            summary: 'Updated an administrative user.',
            metadata: [
                'email' => $user->email,
                'role_slugs' => $user->roles()->orderBy('slug')->pluck('slug')->all(),
                'password_changed' => $request->filled('password'),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    protected function availableRoles()
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('scope')
            ->orderBy('name')
            ->get();
    }
}
