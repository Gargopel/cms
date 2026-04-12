<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.extensions.index') }}" variant="secondary">View Extensions</x-admin.button>
        @can('view_system_health')
            <x-admin.button href="{{ route('admin.health.index') }}" variant="secondary">Open Health</x-admin.button>
        @endcan
        <x-admin.button href="{{ route('admin.maintenance') }}">Open Maintenance</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid--metrics">
        <x-admin.glass-card class="metric-card">
            <span>Total Extensions</span>
            <strong>{{ $metrics['extensions_total'] }}</strong>
            <small>Registro consolidado de plugins e temas sincronizados.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Plugins</span>
            <strong>{{ $metrics['plugins_total'] }}</strong>
            <small>Entradas de plugin conhecidas pelo core.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Enabled</span>
            <strong>{{ $metrics['enabled_total'] }}</strong>
            <small>Extensoes operacionalmente habilitadas.</small>
        </x-admin.glass-card>
        <x-admin.glass-card class="metric-card">
            <span>Risk Surface</span>
            <strong>{{ $metrics['invalid_total'] + $metrics['incompatible_total'] }}</strong>
            <small>Invalidas ou incompativeis que exigem atencao operacional.</small>
        </x-admin.glass-card>
    </div>

    <div class="grid grid--two" style="margin-top: 20px;">
        <x-admin.glass-card title="Extension Status Matrix" subtitle="Leitura rapida dos dois eixos de estado que o core ja separa.">
            <div class="stack">
                <div>
                    <p class="subtle">Discovery Status</p>
                    <div class="list-inline">
                        @foreach ($statusSummary['discovery'] as $item)
                            <div class="mini-stat">
                                <strong>{{ $item['value'] }}</strong>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="subtle">Operational Status</p>
                    <div class="list-inline">
                        @foreach ($statusSummary['operational'] as $item)
                            <div class="mini-stat">
                                <strong>{{ $item['value'] }}</strong>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-admin.glass-card>

        <x-admin.glass-card title="Plugin Bootstrap" subtitle="Observabilidade minima do ultimo ciclo de registro condicional de providers.">
            @if ($bootstrapReport)
                <div class="stack">
                    <div class="list-inline">
                        @foreach ([
                            ['label' => 'Considered', 'value' => $bootstrapReport['report']['summary']['considered']],
                            ['label' => 'Registered', 'value' => $bootstrapReport['report']['summary']['registered']],
                            ['label' => 'Ignored', 'value' => $bootstrapReport['report']['summary']['ignored']],
                            ['label' => 'Failed', 'value' => $bootstrapReport['report']['summary']['failed']],
                        ] as $item)
                            <div class="mini-stat">
                                <strong>{{ $item['value'] }}</strong>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="subtle">Last stored at: {{ $bootstrapReport['stored_at'] ?? 'n/a' }}</p>
                </div>
            @else
                <div class="empty-state">
                    Nenhum relatorio de bootstrap foi persistido ainda. A estrutura ja esta pronta para alimentar admin e observabilidade futura.
                </div>
            @endif
        </x-admin.glass-card>
    </div>

    <div class="grid grid--two" style="margin-top: 20px;">
        @can('view_system_health')
            <x-admin.glass-card title="System Health Snapshot" subtitle="Resumo rapido do diagnostico basico executado pelo core nesta requisicao.">
                <div class="stack">
                    <div class="list-inline">
                        <div class="mini-stat">
                            <strong>{{ $healthSummary['summary']['ok'] }}</strong>
                            <span>Ok</span>
                        </div>
                        <div class="mini-stat">
                            <strong>{{ $healthSummary['summary']['warning'] }}</strong>
                            <span>Warnings</span>
                        </div>
                        <div class="mini-stat">
                            <strong>{{ $healthSummary['summary']['error'] }}</strong>
                            <span>Errors</span>
                        </div>
                    </div>
                    <div>
                        <x-admin.status-badge :value="$healthSummary['overall_status']" />
                    </div>
                </div>
            </x-admin.glass-card>
        @else
            <x-admin.glass-card title="System Health Snapshot" subtitle="Esta visao resumida exige permissao especifica para diagnostico do sistema.">
                <div class="empty-state">
                    Seu usuario atual pode operar o dashboard, mas nao possui acesso ao diagnostico completo de saude do sistema.
                </div>
            </x-admin.glass-card>
        @endcan

        <x-admin.glass-card title="Recent Registry Activity" subtitle="Ultimas extensoes atualizadas no registro persistido.">
            @if ($recentExtensions !== [])
                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Discovery</th>
                                <th>Operational</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentExtensions as $extension)
                                <tr>
                                    <td>
                                        <strong>{{ $extension['name'] ?? 'Unknown extension' }}</strong><br>
                                        <span class="subtle">{{ $extension['slug'] ?? 'no-slug' }}</span>
                                    </td>
                                    <td>{{ $extension['type'] }}</td>
                                    <td><x-admin.status-badge :value="$extension['discovery_status']" /></td>
                                    <td><x-admin.status-badge :value="$extension['operational_status']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    O registro de extensoes ainda nao possui entradas para exibir aqui.
                </div>
            @endif
        </x-admin.glass-card>

        <x-admin.glass-card title="Quick Links" subtitle="Atalhos operacionais desta fase do core.">
            <div class="actions-row">
                <x-admin.button href="{{ route('admin.extensions.index') }}">Go To Extensions</x-admin.button>
                <x-admin.button href="{{ route('admin.maintenance') }}" variant="secondary">Go To Maintenance</x-admin.button>
                @can('view_system_health')
                    <x-admin.button href="{{ route('admin.health.index') }}" variant="secondary">Go To Health</x-admin.button>
                @endcan
            </div>
            <p class="footer-note">
                Esta area permanece intencionalmente enxuta. O objetivo e dar leitura operacional do estado do sistema sem transformar o core em um admin monolitico.
            </p>
        </x-admin.glass-card>
    </div>

    @if (($extensionDashboardPanels ?? []) !== [])
        <div style="margin-top: 28px; margin-bottom: 12px;">
            <div class="page-eyebrow">Plugin Surfaces</div>
            <p class="subtle" style="margin: 8px 0 0;">Blocos simples publicados por plugins validos, instalados e habilitados que usam os pontos de extensao publicos do core.</p>
        </div>
        <div class="grid grid--three" style="margin-top: 20px;">
            @foreach ($extensionDashboardPanels as $panel)
                <x-admin.glass-card title="{{ $panel['title'] }}" subtitle="{{ $panel['description'] }}">
                    <div class="stack">
                        <div class="list-inline">
                            <x-admin.status-badge value="plugin" />
                            @if ($panel['badge'])
                                <x-admin.status-badge :value="$panel['badge']" />
                            @endif
                        </div>
                        <div class="subtle">Source: {{ $panel['plugin_slug'] }}</div>
                        @if ($panel['href'])
                            <div class="actions-row">
                                <x-admin.button href="{{ $panel['href'] }}" variant="secondary">Open Surface</x-admin.button>
                            </div>
                        @endif
                    </div>
                </x-admin.glass-card>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
