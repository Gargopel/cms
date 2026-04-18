<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <div class="table-actions">
            @can(\Plugins\Blog\Enums\BlogPermission::ManageCategories->value)
                <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/categories') }}" variant="secondary">Categories</x-admin.button>
            @endcan
            @can(\Plugins\Blog\Enums\BlogPermission::ManageTags->value)
                <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/tags') }}" variant="secondary">Tags</x-admin.button>
            @endcan
            @can(\Plugins\Blog\Enums\BlogPermission::CreatePosts->value)
                <x-admin.button href="{{ route('plugins.blog.admin.create') }}">Create Post</x-admin.button>
            @endcan
        </div>
    </x-admin.page-header>

    <div class="grid grid--three" style="margin-bottom: 20px;">
        <x-admin.glass-card class="metric-card">
            <span>Total Posts</span>
            <strong>{{ $summary['total'] }}</strong>
            <small>Entradas editoriais persistidas pelo plugin oficial Blog.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Draft</span>
            <strong>{{ $summary['draft'] }}</strong>
            <small>Posts ainda nao publicados.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Published</span>
            <strong>{{ $summary['published'] }}</strong>
            <small>Posts visiveis na listagem publica do blog.</small>
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="Blog Library" subtitle="Listagem editorial minima do plugin oficial Blog.">
        <form method="GET" action="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/posts') }}" class="stack" style="margin-bottom: 18px;">
            <div class="grid grid--three">
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="Title or slug">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" @selected($filters['status'] === 'all')>All statuses</option>
                        <option value="draft" @selected($filters['status'] === 'draft')>Draft</option>
                        <option value="published" @selected($filters['status'] === 'published')>Published</option>
                    </select>
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="0" @selected((int) $filters['category'] === 0)>All categories</option>
                        @foreach ($categoryOptions as $categoryOption)
                            <option value="{{ $categoryOption->getKey() }}" @selected((int) $filters['category'] === $categoryOption->getKey())>{{ $categoryOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-actions">
                <button type="submit" class="admin-button admin-button--secondary">Apply Filters</button>
                <a href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/blog/posts') }}" class="admin-button admin-button--ghost">Reset</a>
            </div>
        </form>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Tags</th>
                        <th>Published</th>
                        <th>Updated</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <strong>{{ $post->title }}</strong><br>
                                <span class="subtle">{{ $post->slug }}</span><br>
                                <span class="subtle">{{ $post->excerpt }}</span>
                            </td>
                            <td><x-admin.status-badge :value="$post->status->value" /></td>
                            <td>{{ $post->category?->name ?? 'Uncategorized' }}</td>
                            <td>{{ $post->tags->pluck('name')->join(', ') ?: 'No tags' }}</td>
                            <td>{{ $post->published_at?->format('Y-m-d H:i') ?? 'n/a' }}</td>
                            <td>{{ $post->updated_at?->format('Y-m-d H:i') ?? 'n/a' }}</td>
                            <td>
                                @if ($post->status === \Plugins\Blog\Enums\PostStatus::Published)
                                    <a href="{{ url('/blog/'.$post->slug) }}" class="subtle">{{ url('/blog/'.$post->slug) }}</a>
                                @else
                                    <span class="subtle">Not public while draft.</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    @can(\Plugins\Blog\Enums\BlogPermission::EditPosts->value)
                                        <x-admin.button href="{{ route('plugins.blog.admin.edit', $post) }}" variant="secondary">Edit</x-admin.button>
                                    @endcan
                                    @can(\Plugins\Blog\Enums\BlogPermission::DeletePosts->value)
                                        <form method="POST" action="{{ route('plugins.blog.admin.destroy', $post) }}">
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
                            <td colspan="8">
                                <div class="empty-state">Nenhum post criado ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($posts, 'links'))
            <div style="margin-top: 18px;" class="subtle">
                Showing {{ $posts->firstItem() ?? 0 }}-{{ $posts->lastItem() ?? 0 }} of {{ $posts->total() }} posts.
            </div>
        @endif
    </x-admin.glass-card>
</x-layouts.admin>
