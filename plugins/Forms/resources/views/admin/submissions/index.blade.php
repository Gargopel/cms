<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <div class="table-actions">
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms') }}" variant="secondary">Back To Forms</x-admin.button>
            @can(\Plugins\Forms\Enums\FormsPermission::EditForms->value)
                <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$formRecord->getKey().'/fields') }}" variant="secondary">Manage Fields</x-admin.button>
            @endcan
        </div>
    </x-admin.page-header>

    <div class="grid" style="gap: 18px;">
        @forelse ($submissions as $submission)
            <x-admin.glass-card :title="'Submission #'.$submission->getKey()" :subtitle="$submission->submitted_at?->format('Y-m-d H:i:s') ?? 'Pending timestamp'">
                <div class="grid grid--two" style="margin-bottom: 16px;">
                    <div class="notice">IP: {{ $submission->ip_address ?? 'n/a' }}</div>
                    <div class="notice">User Agent: {{ $submission->user_agent ?? 'n/a' }}</div>
                </div>

                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submission->values as $value)
                                <tr>
                                    <td>
                                        <strong>{{ $value->field_label }}</strong><br>
                                        <span class="subtle">{{ $value->field_name }}</span>
                                    </td>
                                    <td>{{ $value->value === '1' ? 'Yes' : ($value->value === '0' ? 'No' : ($value->value ?: 'n/a')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.glass-card>
        @empty
            <x-admin.glass-card title="No submissions yet" subtitle="O formulario selecionado ainda nao recebeu respostas persistidas.">
                <div class="empty-state">Nenhuma submissao encontrada.</div>
            </x-admin.glass-card>
        @endforelse
    </div>
</x-layouts.admin>
