<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.roles.index') }}" variant="secondary">Go To Roles</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Permission Catalog" subtitle="Permissoes registradas no sistema, incluindo espaco para escopos futuros vindos de plugins.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Scope</th>
                        <th>Origin</th>
                        <th>Description</th>
                        <th>Roles Using</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr>
                            <td>
                                <strong>{{ $permission->name }}</strong><br>
                                <span class="subtle">{{ $permission->slug }}</span>
                            </td>
                            <td>{{ $permission->scope_label ?? $permission->scope }}</td>
                            <td>{{ $permission->origin_label ?? $permission->scope }}</td>
                            <td>{{ $permission->description ?: 'No description registered.' }}</td>
                            <td>{{ $permission->roles_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Nenhuma permissao registrada ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
