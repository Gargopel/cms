<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms') }}" variant="secondary">Back To Forms</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Form Settings" subtitle="Titulo, slug, descricao, mensagem de sucesso e status do formulario.">
        <form method="POST" action="{{ $submitRoute }}" class="form-grid">
            @csrf
            @if ($submitMethod !== 'POST')
                @method($submitMethod)
            @endif

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $formRecord->title) }}" required>
                </div>

                <div class="field">
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" type="text" value="{{ old('slug', $formRecord->slug) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description', $formRecord->description) }}</textarea>
            </div>

            <div class="field">
                <label for="success_message">Success Message</label>
                <textarea id="success_message" name="success_message" rows="3">{{ old('success_message', $formRecord->success_message) }}</textarea>
            </div>

            <div class="field">
                <label for="status">Status</label>
                @php
                    $canPublish = auth()->user()?->can(\Plugins\Forms\Enums\FormsPermission::PublishForms->value) ?? false;
                    $currentStatus = old('status', $formRecord->status?->value ?? \Plugins\Forms\Enums\FormStatus::Draft->value);
                @endphp
                @if ($canPublish)
                    <select id="status" name="status">
                        @foreach (\Plugins\Forms\Enums\FormStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <div class="notice">
                        O status publico e protegido pela permissao `forms.publish_forms`. Nesta edicao, o formulario permanece em <strong>{{ $currentStatus }}</strong>.
                    </div>
                @endif
            </div>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--primary">Save Form</button>
                <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms') }}" variant="secondary">Cancel</x-admin.button>
            </div>
        </form>
    </x-admin.glass-card>
</x-layouts.admin>
