<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <div class="table-actions">
            <x-admin.button href="{{ route('plugins.blog.admin.index') }}" variant="secondary">Back To Posts</x-admin.button>
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/tags/create') }}">Create Tag</x-admin.button>
        </div>
    </x-admin.page-header>

    <x-admin.glass-card title="Tag Library" subtitle="Tags editoriais simples para classificar posts por assunto sem introduzir taxonomia generica.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Posts</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tags as $tag)
                        <tr>
                            <td>
                                <strong>{{ $tag->name }}</strong><br>
                                <span class="subtle">{{ $tag->slug }}</span><br>
                                <span class="subtle">{{ $tag->description ?: 'No description.' }}</span>
                            </td>
                            <td>{{ $tag->posts_count }}</td>
                            <td>
                                <a href="{{ url('/blog/tag/'.$tag->slug) }}" class="subtle">
                                    {{ url('/blog/tag/'.$tag->slug) }}
                                </a>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/tags/'.$tag->getKey().'/edit') }}" variant="secondary">Edit</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">Nenhuma tag criada ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
