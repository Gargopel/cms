<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.roles.create') }}">Create Role</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Role Registry" subtitle="Cargos disponiveis no core e seu impacto atual sobre usuarios e permissoes.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Scope</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>
                                <strong>{{ $role->name }}</strong><br>
                                <span class="subtle">{{ $role->slug }}</span>
                            </td>
                            <td>{{ $role->scope }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>
                                <div class="table-actions">
                                    <x-admin.button href="{{ route('admin.roles.edit', $role) }}" variant="secondary">Edit</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Nenhum cargo encontrado ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
