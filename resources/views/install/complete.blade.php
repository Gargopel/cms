<x-layouts.installer :page-title="$pageTitle" :steps="$steps" :current-step="$currentStep">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">Complete</span>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <div class="stack">
        <div class="metric-list">
            <div class="metric-item">
                <strong>Installed at</strong>
                <span class="subtle">{{ $completed['installed_at'] }}</span>
            </div>
            <div class="metric-item">
                <strong>Core version</strong>
                <span class="subtle">{{ $completed['core_version'] }}</span>
            </div>
            <div class="metric-item">
                <strong>Admin email</strong>
                <span class="subtle">{{ $completed['admin_email'] }}</span>
            </div>
            <div class="metric-item">
                <strong>Application URL</strong>
                <span class="subtle">{{ $completed['app_url'] }}</span>
            </div>
        </div>

        <div class="notice">
            A instalacao foi marcada como concluida. O instalador web passa a ser bloqueado e o proximo passo e entrar no painel administrativo.
        </div>

        <div class="actions">
            <a class="admin-button admin-button--primary" href="{{ route('admin.login') }}">Go To Admin Login</a>
        </div>
    </div>
</x-layouts.installer>
