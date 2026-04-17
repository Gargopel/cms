<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('plugins.blog.admin.index') }}" variant="secondary">Back To Blog</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Post Form" subtitle="Titulo, slug, resumo, conteudo e status editorial controlado por permissao real.">
        <form method="POST" action="{{ $submitRoute }}" class="form-grid">
            @csrf
            @if ($submitMethod !== 'POST')
                @method($submitMethod)
            @endif

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $postRecord->title) }}" required>
                </div>

                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $postRecord->slug) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" rows="3" required>{{ old('excerpt', $postRecord->excerpt) }}</textarea>
            </div>

            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">No category</option>
                    @foreach ($categoryOptions as $category)
                        <option value="{{ $category->getKey() }}" @selected((string) old('category_id', $postRecord->category_id) === (string) $category->getKey())>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <span class="stat-note">Uma categoria principal por post nesta fase editorial do plugin.</span>
                @error('category_id')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="tag_ids">Tags</label>
                @php
                    $selectedTagIds = collect(old('tag_ids', $postRecord->relationLoaded('tags') ? $postRecord->tags->pluck('id')->all() : []))
                        ->map(fn ($value) => (string) $value)
                        ->all();
                @endphp
                <select id="tag_ids" name="tag_ids[]" multiple size="6">
                    @foreach ($tagOptions as $tag)
                        <option value="{{ $tag->getKey() }}" @selected(in_array((string) $tag->getKey(), $selectedTagIds, true))>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </select>
                <span class="stat-note">Tags multiplas por post nesta fase editorial, sem virar taxonomia generica.</span>
                @error('tag_ids')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
                @error('tag_ids.*')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="content">Content</label>
                <textarea id="content" name="content" required>{{ old('content', $postRecord->content) }}</textarea>
            </div>

            <div class="field">
                <label for="featured_image_id">Featured Image</label>
                <select id="featured_image_id" name="featured_image_id">
                    <option value="">No featured image</option>
                    @foreach ($featuredImageOptions as $asset)
                        <option value="{{ $asset->getKey() }}" @selected((string) old('featured_image_id', $postRecord->featured_image_id) === (string) $asset->getKey())>
                            {{ $asset->original_name }} ({{ $asset->humanSize() }})
                        </option>
                    @endforeach
                </select>
                <span class="stat-note">Selecione uma imagem ja existente na biblioteca de midia do core.</span>
                @error('featured_image_id')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
            </div>

            @if ($postRecord->featuredImage?->url())
                <div class="notice">
                    Featured image atual: <a href="{{ $postRecord->featuredImage->url() }}" target="_blank" rel="noopener">{{ $postRecord->featuredImage->original_name }}</a>
                </div>
            @endif

            <div class="field">
                <label for="status">Status</label>
                @php
                    $canPublish = auth()->user()?->can(\Plugins\Blog\Enums\BlogPermission::PublishPosts->value) ?? false;
                    $currentStatus = old('status', $postRecord->status?->value ?? \Plugins\Blog\Enums\PostStatus::Draft->value);
                @endphp
                @if ($canPublish)
                    <select id="status" name="status">
                        @foreach (\Plugins\Blog\Enums\PostStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <div class="notice">
                        O status editorial publicado e protegido pela permissao `blog.publish_posts`. Nesta edicao, o post permanece em <strong>{{ $currentStatus }}</strong>.
                    </div>
                @endif
            </div>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--primary">Save Post</button>
                <x-admin.button href="{{ route('plugins.blog.admin.index') }}" variant="secondary">Cancel</x-admin.button>
            </div>
        </form>
    </x-admin.glass-card>
</x-layouts.admin>
