<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        @can(\Plugins\Forms\Enums\FormsPermission::CreateForms->value)
            <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/create') }}">Create Form</x-admin.button>
        @endcan
    </x-admin.page-header>

    <div class="grid grid--four" style="margin-bottom: 20px;">
        <x-admin.glass-card class="metric-card">
            <span>Total Forms</span>
            <strong>{{ $summary['total'] }}</strong>
            <small>Formularios persistidos pelo plugin oficial Forms.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Draft</span>
            <strong>{{ $summary['draft'] }}</strong>
            <small>Ainda indisponiveis publicamente.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Published</span>
            <strong>{{ $summary['published'] }}</strong>
            <small>Disponiveis para submissao publica.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Submissions</span>
            <strong>{{ $summary['submissions'] }}</strong>
            <small>Entradas persistidas de formularios publicados.</small>
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="Forms Library" subtitle="Listagem operacional minima do plugin oficial Forms.">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Fields</th>
                        <th>Submissions</th>
                        <th>Public URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td>
                                <strong>{{ $form->title }}</strong><br>
                                <span class="subtle">{{ $form->slug }}</span><br>
                                <span class="subtle">{{ $form->description ?: 'No description yet.' }}</span>
                            </td>
                            <td><x-admin.status-badge :value="$form->status->value" /></td>
                            <td>{{ $form->fields_count }}</td>
                            <td>{{ $form->submissions_count }}</td>
                            <td>
                                @if ($form->status === \Plugins\Forms\Enums\FormStatus::Published)
                                    <a href="{{ url('/forms/'.$form->slug) }}" class="subtle">{{ url('/forms/'.$form->slug) }}</a>
                                @else
                                    <span class="subtle">Not public while draft.</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    @can(\Plugins\Forms\Enums\FormsPermission::EditForms->value)
                                        <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$form->getKey().'/fields') }}" variant="secondary">Fields</x-admin.button>
                                        <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$form->getKey().'/edit') }}" variant="secondary">Edit</x-admin.button>
                                    @endcan
                                    @can(\Plugins\Forms\Enums\FormsPermission::ViewFormSubmissions->value)
                                        <x-admin.button href="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$form->getKey().'/submissions') }}" variant="secondary">Submissions</x-admin.button>
                                    @endcan
                                    @can(\Plugins\Forms\Enums\FormsPermission::DeleteForms->value)
                                        <form method="POST" action="{{ url('/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/forms/'.$form->getKey()) }}">
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
                            <td colspan="6">
                                <div class="empty-state">Nenhum formulario criado ainda.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
