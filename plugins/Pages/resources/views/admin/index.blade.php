<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        @can(\Plugins\Pages\Enums\PagesPermission::CreatePages->value)
            <x-admin.button href="{{ route('plugins.pages.admin.create') }}">Create Page</x-admin.button>
        @endcan
    </x-admin.page-header>

    <div class="grid grid--three" style="margin-bottom: 20px;">
        <x-admin.glass-card class="metric-card">
            <span>Total Pages</span>
            <strong>{{ $summary['total'] }}</strong>
            <small>Entradas persistidas pelo plugin oficial Pages.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Draft</span>
            <strong>{{ $summary['draft'] }}</strong>
            <small>Paginas ainda nao publicadas.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Published</span>
            <strong>{{ $summary['published'] }}</strong>
            <small>Paginas visiveis na rota publica.</small>
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="Pages Library" subtitle="Listagem operacional minima para o primeiro plugin oficial do ecossistema.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>
                                <strong>{{ $page->title }}</strong><br>
                                <span class="subtle">{{ $page->slug }}</span>
                            </td>
                            <td><x-admin.status-badge :value="$page->status->value" /></td>
                            <td>{{ $page->updated_at?->format('Y-m-d H:i') ?? 'n/a' }}</td>
                            <td>
                                @if ($page->status === \Plugins\Pages\Enums\PageStatus::Published)
                                    <a href="{{ route('plugins.pages.public.show', $page->slug) }}" class="subtle">{{ route('plugins.pages.public.show', $page->slug) }}</a>
                                @else
                                    <span class="subtle">Not public while draft.</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    @can(\Plugins\Pages\Enums\PagesPermission::EditPages->value)
                                        <x-admin.button href="{{ route('plugins.pages.admin.edit', $page) }}" variant="secondary">Edit</x-admin.button>
                                    @endcan
                                    @can(\Plugins\Pages\Enums\PagesPermission::DeletePages->value)
                                        <form method="POST" action="{{ route('plugins.pages.admin.destroy', $page) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-button admin-button--danger">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Nenhuma pagina criada ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
