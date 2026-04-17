<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <div class="table-actions">
            <x-admin.button href="{{ route('plugins.blog.admin.index') }}" variant="secondary">Back To Posts</x-admin.button>
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/categories/create') }}">Create Category</x-admin.button>
        </div>
    </x-admin.page-header>

    <x-admin.glass-card title="Category Library" subtitle="Categorias editoriais simples para agrupar posts sem virar taxonomia generica.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Posts</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <strong>{{ $category->name }}</strong><br>
                                <span class="subtle">{{ $category->slug }}</span><br>
                                <span class="subtle">{{ $category->description ?: 'No description.' }}</span>
                            </td>
                            <td>{{ $category->posts_count }}</td>
                            <td>
                                <a href="{{ url('/blog/category/'.$category->slug) }}" class="subtle">
                                    {{ url('/blog/category/'.$category->slug) }}
                                </a>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/categories/'.$category->getKey().'/edit') }}" variant="secondary">Edit</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">Nenhuma categoria criada ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
