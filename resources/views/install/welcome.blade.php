<x-layouts.installer :page-title="$pageTitle" :steps="$steps" :current-step="$currentStep">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">Welcome</span>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <div class="stack">
        <div class="notice">
            Este instalador prepara apenas a base do produto: configuracao inicial, banco, seguranca administrativa e bloqueio de reinstalacao.
        </div>

        <div class="metric-list">
            <div class="metric-item">
                <strong>Environment check</strong>
                <span class="subtle">Valida PHP, extensoes e permissoes minimas antes do setup.</span>
            </div>
            <div class="metric-item">
                <strong>Database setup</strong>
                <span class="subtle">Testa a conexao, grava a configuracao e roda migrations com seguranca.</span>
            </div>
            <div class="metric-item">
                <strong>Admin bootstrap</strong>
                <span class="subtle">Cria o primeiro administrador integrado ao sistema real de roles e permissions.</span>
            </div>
            <div class="metric-item">
                <strong>Explicit install state</strong>
                <span class="subtle">Marca a instalacao como concluida e impede reinstalacao acidental.</span>
            </div>
        </div>

        <div class="actions">
            <a class="admin-button admin-button--primary" href="{{ route('install.database') }}">Start Installation</a>
            <a class="admin-button admin-button--secondary" href="{{ route('install.requirements') }}">Review Requirements</a>
        </div>
    </div>
</x-layouts.installer>
