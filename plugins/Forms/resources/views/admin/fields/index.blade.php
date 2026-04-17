<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <div class="table-actions">
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms') }}" variant="secondary">Back To Forms</x-admin.button>
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$formRecord->getKey().'/fields/create') }}">Create Field</x-admin.button>
        </div>
    </x-admin.page-header>

    <x-admin.glass-card :title="$formRecord->title" subtitle="Campos ordenados e previsiveis do formulario selecionado.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Order</th>
                        <th>Options</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fields as $field)
                        <tr>
                            <td>
                                <strong>{{ $field->label }}</strong><br>
                                <span class="subtle">{{ $field->name }}</span><br>
                                <span class="subtle">{{ $field->help_text ?: 'No help text.' }}</span>
                            </td>
                            <td><x-admin.status-badge :value="$field->type->value" /></td>
                            <td>{{ $field->is_required ? 'Yes' : 'No' }}</td>
                            <td>{{ $field->sort_order }}</td>
                            <td>{{ count($field->optionValues()) ?: 'n/a' }}</td>
                            <td>
                                <div class="table-actions">
                                    <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$formRecord->getKey().'/fields/'.$field->getKey().'/edit') }}" variant="secondary">Edit</x-admin.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">Nenhum campo configurado ainda para este formulario.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
