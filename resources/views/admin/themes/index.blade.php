<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        <x-admin.button href="{{ route('admin.dashboard') }}" variant="secondary">Back To Dashboard</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid--two">
        <x-admin.glass-card title="Active Theme" subtitle="Tema atualmente usado pelo frontend com fallback seguro para views do core.">
            @if ($activeTheme)
                <div class="stack">
                    <div class="mini-stat">
                        <strong>{{ $activeTheme->name }}</strong>
                        <span>{{ $activeTheme->slug }}</span>
                    </div>
                    <span class="stat-note">Version: {{ $activeTheme->detected_version ?? 'n/a' }}</span>
                    <span class="stat-note">Path: {{ $activeTheme->path }}</span>
                </div>
            @else
                <div class="empty-state">
                    Nenhum tema ativo elegivel foi resolvido. O frontend continua usando fallback de views do core.
                </div>
            @endif
        </x-admin.glass-card>

        <x-admin.glass-card title="Themes Summary" subtitle="Leitura do registro de temas descobertos pelo core.">
            <div class="list-inline">
                <div class="mini-stat">
                    <strong>{{ $summary['total'] }}</strong>
                    <span>Total</span>
                </div>
                <div class="mini-stat">
                    <strong>{{ $summary['valid'] }}</strong>
                    <span>Valid</span>
                </div>
                <div class="mini-stat">
                    <strong>{{ $summary['installed'] }}</strong>
                    <span>Installed</span>
                </div>
            </div>
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="Discovered Themes" subtitle="Lista de temas conhecidos pelo registry, com status de validade, lifecycle e capacidade de ativacao." style="margin-top: 20px;">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Version</th>
                        <th>Discovery</th>
                        <th>Lifecycle</th>
                        <th>Views / Assets</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($themes as $theme)
                        @php($state = $themeStates[$theme->getKey()] ?? null)
                        <tr>
                            <td>
                                <strong>{{ $theme->name ?? 'Unknown theme' }}</strong><br>
                                <span class="subtle">{{ $theme->slug ?? 'no-slug' }}</span>
                                @if ($state['is_active'] ?? false)
                                    <br><x-admin.status-badge value="active" class="status-badge--success" />
                                @endif
                                @if (!empty($theme->manifest_warnings))
                                    @foreach ($theme->manifest_warnings as $warning)
                                        <br><span class="stat-note">{{ $warning }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $theme->detected_version ?? 'n/a' }}</td>
                            <td><x-admin.status-badge :value="$theme->discovery_status?->value ?? 'unknown'" /></td>
                            <td><x-admin.status-badge :value="$theme->administrativeLifecycleStatus()->value" /></td>
                            <td>
                                <div class="stack">
                                    <span class="stat-note">Views: {{ ($state['has_views'] ?? false) ? 'ready' : 'fallback only' }}</span>
                                    <span class="stat-note">Assets: {{ ($state['has_assets'] ?? false) ? 'prepared' : 'not prepared' }}</span>
                                    <span class="stat-note">{{ $state['views_path'] ?? '' }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($canManageThemes)
                                    <div class="table-actions">
                                        @if (($state['is_active'] ?? false) === false && ($state['eligibility']['allowed'] ?? false))
                                            <form method="POST" action="{{ route('admin.themes.activate', $theme) }}">
                                                @csrf
                                                <x-admin.button type="submit">Activate</x-admin.button>
                                            </form>
                                        @elseif ($state['is_active'] ?? false)
                                            <span class="subtle">Theme active.</span>
                                        @else
                                            <span class="subtle">Action unavailable.</span>
                                        @endif
                                    </div>

                                    @if (($state['eligibility'] ?? null) && ! ($state['eligibility']['allowed'] ?? false) && ! empty($state['eligibility']['blocks']))
                                        <div class="stack" style="margin-top: 10px;">
                                            <span class="stat-note">{{ $state['eligibility']['blocks'][0]['message'] }}</span>
                                        </div>
                                    @endif

                                    @if (($state['eligibility'] ?? null) && ! empty($state['eligibility']['warnings']))
                                        <div class="stack" style="margin-top: 10px;">
                                            @foreach ($state['eligibility']['warnings'] as $warning)
                                                <span class="stat-note">{{ $warning['message'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <span class="subtle">Read only.</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    Nenhum tema foi registrado ainda. Sincronize extensoes para popular o registry do core.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($themes, 'links'))
            <div style="margin-top: 18px;" class="subtle">
                Showing {{ $themes->firstItem() ?? 0 }}-{{ $themes->lastItem() ?? 0 }} of {{ $themes->total() }} discovered themes.
            </div>
        @endif
    </x-admin.glass-card>
</x-layouts.admin>
