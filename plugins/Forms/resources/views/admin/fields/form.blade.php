<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$formRecord->getKey().'/fields') }}" variant="secondary">Back To Fields</x-admin.button>
    </x-admin.page-header>

    <x-admin.glass-card title="Field Settings" subtitle="Contrato pequeno de campos suportados nesta fase: text, email, textarea, select e checkbox.">
        <form method="POST" action="{{ $submitRoute }}" class="form-grid">
            @csrf
            @if ($submitMethod !== 'POST')
                @method($submitMethod)
            @endif

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="label">Label</label>
                    <input id="label" name="label" type="text" value="{{ old('label', $fieldRecord->label) }}" required>
                </div>

                <div class="field">
                    <label for="name">Field Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $fieldRecord->name) }}" required>
                </div>
            </div>

            <div class="form-grid form-grid--two">
                <div class="field">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        @foreach ($fieldTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('type', $fieldRecord->type?->value ?? \Plugins\Forms\Enums\FormFieldType::Text->value) === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="sort_order">Sort Order</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="10000" value="{{ old('sort_order', $fieldRecord->sort_order ?? 10) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="placeholder">Placeholder</label>
                <input id="placeholder" name="placeholder" type="text" value="{{ old('placeholder', $fieldRecord->placeholder) }}">
            </div>

            <div class="field">
                <label for="help_text">Help Text</label>
                <textarea id="help_text" name="help_text" rows="3">{{ old('help_text', $fieldRecord->help_text) }}</textarea>
            </div>

            <div class="field">
                <label for="options_text">Select Options</label>
                <textarea id="options_text" name="options_text" rows="5">{{ old('options_text', implode(PHP_EOL, $fieldRecord->optionValues())) }}</textarea>
                <span class="stat-note">Use uma opcao por linha. So e aplicado quando o tipo do campo for `select`.</span>
                @error('options_text')
                    <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                @enderror
            </div>

            <label class="checkbox-row">
                <input name="is_required" type="checkbox" value="1" @checked(old('is_required', $fieldRecord->is_required))>
                <span>Required field</span>
            </label>

            <div class="actions-row">
                <button type="submit" class="admin-button admin-button--primary">Save Field</button>
                <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$formRecord->getKey().'/fields') }}" variant="secondary">Cancel</x-admin.button>
            </div>
        </form>
    </x-admin.glass-card>
</x-layouts.admin>
