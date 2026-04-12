<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" eyebrow="Core Audit" />

    <div class="grid grid--two">
        <x-admin.glass-card title="Filters" subtitle="Use filtros simples para leitura operacional por acao, usuario e periodo.">
            <form method="GET" action="{{ route('admin.audit.index') }}">
                <div class="form-grid form-grid--two">
                    <div class="field">
                        <label for="action">Action</label>
                        <input id="action" name="action" list="audit-action-options" value="{{ $filters['action'] }}">
                        <datalist id="audit-action-options">
                            @foreach ($actionOptions as $actionOption)
                                <option value="{{ $actionOption }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="field">
                        <label for="user_id">User</label>
                        <select id="user_id" name="user_id">
                            <option value="">All users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected($filters['user_id'] !== '' && (int) $filters['user_id'] === $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid form-grid--two" style="margin-top: 16px;">
                    <div class="field">
                        <label for="date_from">Date From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="field">
                        <label for="date_to">Date To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}">
                    </div>
                </div>

                <div class="actions-row" style="margin-top: 18px;">
                    <button type="submit" class="admin-button admin-button--primary">Apply Filters</button>
                    <a href="{{ route('admin.audit.index') }}" class="admin-button admin-button--secondary">Reset</a>
                </div>
            </form>
        </x-admin.glass-card>

        <x-admin.glass-card title="What Is Logged" subtitle="Escopo inicial e intencionalmente enxuto do core nesta fase.">
            <div class="stack">
                <div class="notice">Login e logout administrativo.</div>
                <div class="notice">Criacao e edicao de usuarios do core.</div>
                <div class="notice">Criacao e edicao de cargos, incluindo alteracoes de permissoes.</div>
                <div class="notice">Atualizacao de settings globais do core.</div>
                <div class="notice">Acoes de manutencao suportadas pelo painel.</div>
            </div>
        </x-admin.glass-card>
    </div>

    <div style="margin-top: 20px;">
        <x-admin.glass-card title="Audit Trail" subtitle="Historico recente com usuario responsavel, alvo, resumo e contexto tecnico basico.">
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>User</th>
                            <th>Target</th>
                            <th>Summary</th>
                            <th>Context</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td><x-admin.status-badge :value="$log->action" /></td>
                                <td>
                                    @if ($log->user)
                                        <strong>{{ $log->user->name }}</strong><br>
                                        <span class="subtle">{{ $log->user->email }}</span>
                                    @else
                                        <span class="subtle">System / Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->target_type || $log->target_id)
                                        <code>{{ class_basename((string) $log->target_type) }}{{ $log->target_id ? '#'.$log->target_id : '' }}</code>
                                    @else
                                        <span class="subtle">n/a</span>
                                    @endif
                                </td>
                                <td>{{ $log->summary ?? 'n/a' }}</td>
                                <td>
                                    <div class="stack">
                                        @if ($log->ip_address)
                                            <span class="subtle">IP: {{ $log->ip_address }}</span>
                                        @endif
                                        @if ($log->metadata)
                                            <code>{{ \Illuminate\Support\Str::limit(json_encode($log->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 220) }}</code>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') ?? 'n/a' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">Nenhum evento de auditoria encontrado para os filtros informados.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($logs, 'links'))
                <div style="margin-top: 18px;">
                    {{ $logs->links() }}
                </div>
            @endif
        </x-admin.glass-card>
    </div>
</x-layouts.admin>
