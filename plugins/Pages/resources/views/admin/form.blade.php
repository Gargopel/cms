<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('plugins.pages.admin.index') }}" variant="secondary">Back To Pages</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Page Form" subtitle="Titulo, slug, conteudo e status publico controlado por permissao real.">
        <form method="POST" action="{{ $submitRoute }}" class="form-grid">
            @csrf
            @if ($submitMethod !== 'POST')
                @method($submitMethod)
            @endif

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $pageRecord->title) }}" required>
                </div>

                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $pageRecord->slug) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="content">Content</label>
                <textarea id="content" name="content" required>{{ old('content', $pageRecord->content) }}</textarea>
            </div>

            <div class="field">
                <label for="featured_image_id">Featured Image</label>
                <select id="featured_image_id" name="featured_image_id">
                    <option value="">No featured image</option>
                    @foreach ($featuredImageOptions as $asset)
                        <option value="{{ $asset->getKey() }}" @selected((string) old('featured_image_id', $pageRecord->featured_image_id) === (string) $asset->getKey())>
                            {{ $asset->original_name }} ({{ $asset->humanSize() }})
                        </option>
                    @endforeach
                </select>
                <span class="stat-note">Selecione uma imagem ja existente na biblioteca de midia do core.</span>
                @error('featured_image_id')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
            </div>

            @if ($pageRecord->featuredImage?->url())
                <div class="notice">
                    Featured image atual: <a href="{{ $pageRecord->featuredImage->url() }}" target="_blank" rel="noopener">{{ $pageRecord->featuredImage->original_name }}</a>
                </div>
            @endif

            <div class="field">
                <label for="status">Status</label>
                @php
                    $canPublish = auth()->user()?->can(\Plugins\Pages\Enums\PagesPermission::PublishPages->value) ?? false;
                    $currentStatus = old('status', $pageRecord->status?->value ?? \Plugins\Pages\Enums\PageStatus::Draft->value);
                @endphp
                @if ($canPublish)
                    <select id="status" name="status">
                        @foreach (\Plugins\Pages\Enums\PageStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <div class="notice">
                        O status publico e protegido pela permissao `pages.publish_pages`. Nesta edicao, a pagina permanece em <strong>{{ $currentStatus }}</strong>.
                    </div>
                @endif
            </div>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--primary">Save Page</button>
                <x-admin.button href="{{ route('plugins.pages.admin.index') }}" variant="secondary">Cancel</x-admin.button>
            </div>
        </form>
    </x-admin.glass-card>
</x-layouts.admin>
