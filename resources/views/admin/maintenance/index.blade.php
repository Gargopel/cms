<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.dashboard') }}" variant="secondary">Back To Dashboard</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid--two">
        <x-admin.glass-card title="Safe Cache Operations" subtitle="Acoes suportadas pelo core nesta fase, sem introduzir lifecycle pesado ou magia operacional.">
            <div class="actions-row">
                <form method="POST" action="{{ route('admin.maintenance.cache.application-clear') }}">
                    @csrf
                    <x-admin.button type="submit">Clear Application Cache</x-admin.button>
                </form>

                <form method="POST" action="{{ route('admin.maintenance.cache.views-clear') }}">
                    @csrf
                    <x-admin.button type="submit" variant="secondary">Clear Compiled Views</x-admin.button>
                </form>
            </div>
            <p class="footer-note">
                Estas acoes existem para manutencao segura do core enquanto o painel ainda esta em fundacao.
            </p>
        </x-admin.glass-card>

        <x-admin.glass-card title="Bootstrap Snapshot" subtitle="Ultimo relatorio persistido do registro condicional de providers.">
            @if ($bootstrapReport)
                <div class="list-inline">
                    @foreach ([
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
                <p class="footer-note">Stored at {{ $bootstrapReport['stored_at'] ?? 'n/a' }}</p>
            @else
                <div class="empty-state">
                    Ainda nao existe snapshot de bootstrap disponivel nesta instalacao.
                </div>
            @endif
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="System Status" subtitle="Leitura basica do ambiente e da plataforma, sem extrapolar para diagnostics completos." style="margin-top: 20px;">
        <div class="key-value">
            @foreach ($systemStatus as $item)
                <div class="key-value-item">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                </div>
            @endforeach
        </div>
    </x-admin.glass-card>
</x-layouts.admin>
