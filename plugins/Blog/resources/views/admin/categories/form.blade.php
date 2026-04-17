<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ $indexPath }}" variant="secondary">Back To Categories</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Category Form" subtitle="Nome, slug e descricao opcional da categoria editorial.">
        <form method="POST" action="{{ $submitRoute }}" class="form-grid">
            @csrf
            @if ($submitMethod !== 'POST')
                @method($submitMethod)
            @endif

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $categoryRecord->name) }}" required>
                </div>

                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $categoryRecord->slug) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4">{{ old('description', $categoryRecord->description) }}</textarea>
            </div>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--primary">Save Category</button>
                <x-admin.button href="{{ $indexPath }}" variant="secondary">Cancel</x-admin.button>
            </div>
        </form>
    </x-admin.glass-card>
</x-layouts.admin>
