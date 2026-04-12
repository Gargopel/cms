<x-layouts.installer :page-title="$pageTitle" :steps="$steps" :current-step="$currentStep">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">Requirements</span>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <div class="stack">
        <div>
            <h3>Runtime</h3>
            <div class="status-list" style="margin-top: 12px;">
                @foreach ($report['requirements'] as $item)
                    <div class="status-item">
                        <div>
                            <strong>{{ $item['label'] }}</strong><br>
                            <span class="subtle">{{ $item['details'] }}</span>
                        </div>
                        <span class="status-pill {{ $item['passed'] ? 'status-pill--success' : 'status-pill--danger' }}">
                            {{ $item['passed'] ? 'Pass' : 'Fail' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h3>Permissions</h3>
            <div class="status-list" style="margin-top: 12px;">
                @foreach ($report['permissions'] as $item)
                    <div class="status-item">
                        <div>
                            <strong>{{ $item['label'] }}</strong><br>
                            <span class="subtle">{{ $item['details'] }}</span>
                        </div>
                        <span class="status-pill {{ $item['passed'] ? 'status-pill--success' : 'status-pill--danger' }}">
                            {{ $item['passed'] ? 'Pass' : 'Fail' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="actions">
            <a class="admin-button admin-button--secondary" href="{{ route('install.welcome') }}">Back</a>
            <a class="admin-button admin-button--primary" href="{{ route('install.database') }}">Continue</a>
        </div>
    </div>
</x-layouts.installer>
