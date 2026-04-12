<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.users.create') }}">Create User</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="User Directory" subtitle="Usuarios conhecidos pelo core e seus cargos atuais.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <span class="subtle">{{ $user->email }}</span>
                            </td>
                            <td>
                                <div class="stack">
                                    @forelse ($user->roles as $role)
                                        <span class="subtle">{{ $role->name }} <span class="stat-note">({{ $role->slug }})</span></span>
                                    @empty
                                        <span class="subtle">No roles assigned.</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <x-admin.status-badge :value="$user->isCoreAdministrator() ? 'super admin' : 'managed user'" />
                            </td>
                            <td>{{ optional($user->updated_at)->format('Y-m-d H:i') ?? 'n/a' }}</td>
                            <td>
                                <div class="table-actions">
                                    <x-admin.button href="{{ route('admin.users.edit', $user) }}" variant="secondary">Edit</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Nenhum usuario encontrado ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
