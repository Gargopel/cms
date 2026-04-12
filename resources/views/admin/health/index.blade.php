<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" eyebrow="Core Health">
        <x-admin.status-badge :value="$report['overall_status']" />
    </x-admin.page-header>

    <div class="grid grid--metrics">
        <x-admin.glass-card class="metric-card">
            <span>Total Checks</span>
            <strong>{{ $report['summary']['total'] }}</strong>
            <small>Checks executados nesta leitura do admin.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Ok</span>
            <strong>{{ $report['summary']['ok'] }}</strong>
            <small>Checks sem risco operacional conhecido.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Warnings</span>
            <strong>{{ $report['summary']['warning'] }}</strong>
            <small>Alertas que merecem atencao, sem indicar falha total.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Errors</span>
            <strong>{{ $report['summary']['error'] }}</strong>
            <small>Falhas relevantes para operacao segura do core.</small>
        </x-admin.glass-card>
    </div>

    <div style="margin-top: 20px;">
        <x-admin.glass-card title="Health Checks" subtitle="Resultado estruturado e legivel dos checks basicos de saude do sistema.">
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Check</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['checks'] as $check)
                            <tr>
                                <td>
                                    <strong>{{ $check['label'] }}</strong><br>
                                    <span class="subtle">{{ $check['description'] }}</span>
                                </td>
                                <td>
                                    <x-admin.status-badge :value="$check['status']" />
                                </td>
                                <td>{{ $check['message'] }}</td>
                                <td>
                                    @if ($check['key'] === 'extensions_ecosystem' && !empty($check['meta']['summary']))
                                        <div class="stack">
                                            <span class="stat-note">Total: {{ $check['meta']['summary']['total'] }}</span>
                                            <span class="stat-note">Ok: {{ $check['meta']['summary']['ok'] }}</span>
                                            <span class="stat-note">Warnings: {{ $check['meta']['summary']['warning'] }}</span>
                                            <span class="stat-note">Errors: {{ $check['meta']['summary']['error'] }}</span>
                                            <span class="stat-note">Issues: {{ $check['meta']['summary']['issues'] }}</span>

                                            @if (!empty($check['meta']['top_issues']))
                                                @foreach ($check['meta']['top_issues'] as $issue)
                                                    <span class="stat-note">{{ $issue['extension']['slug'] ?? 'unknown' }}: {{ $issue['message'] }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                    @elseif (($check['meta'] ?? []) !== [])
                                        <code>{{ \Illuminate\Support\Str::limit(json_encode($check['meta'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 240) }}</code>
                                    @else
                                        <span class="subtle">n/a</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.glass-card>
    </div>
</x-layouts.admin>
