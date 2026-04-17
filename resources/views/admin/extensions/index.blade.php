<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle">
        @if ($canManageExtensions)
            <form method="POST" action="{{ route('admin.extensions.sync') }}">
                @csrf
                <x-admin.button type="submit">Sync Extensions</x-admin.button>
            </form>
        @endif
        <x-admin.button href="{{ route('admin.dashboard') }}" variant="secondary">Back To Dashboard</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid--two">
        <x-admin.glass-card title="Registry Summary" subtitle="Leitura do estado persistido usado pelo runtime do core.">
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
                <div>
                    <p class="subtle">Administrative Lifecycle</p>
                    <div class="list-inline">
                        @foreach ($statusSummary['lifecycle'] as $item)
                            <div class="mini-stat">
                                <strong>{{ $item['value'] }}</strong>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-admin.glass-card>

        <x-admin.glass-card title="Bootstrap Snapshot" subtitle="Ultimo estado observado do boot condicional de providers de plugin.">
            @if ($bootstrapReport)
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
                <p class="footer-note">Stored at {{ $bootstrapReport['stored_at'] ?? 'n/a' }}</p>
            @else
                <div class="empty-state">
                    O bootstrap ainda nao produziu um snapshot persistido nesta instalacao.
                </div>
            @endif
        </x-admin.glass-card>
    </div>

    <x-admin.glass-card title="Extensions Health" subtitle="Diagnostico operacional do ecossistema de extensoes com foco em manifesto, dependencias e estado persistido." style="margin-top: 20px;">
        <div class="list-inline">
            <div class="mini-stat">
                <strong>{{ $healthSummary['summary']['total'] }}</strong>
                <span>Total</span>
            </div>
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
            <div class="mini-stat">
                <strong>{{ $healthSummary['summary']['issues'] }}</strong>
                <span>Issues</span>
            </div>
        </div>

        @if (!empty($healthSummary['top_issues']))
            <div class="stack" style="margin-top: 16px;">
                @foreach ($healthSummary['top_issues'] as $issue)
                    <span class="stat-note">{{ $issue['extension']['slug'] ?? 'unknown' }}: {{ $issue['message'] }}</span>
                @endforeach
            </div>
        @else
            <div class="empty-state" style="margin-top: 16px;">
                Nenhum problema operacional relevante de extensoes foi detectado nesta leitura.
            </div>
        @endif
    </x-admin.glass-card>

    <x-admin.glass-card title="Registered Extensions" subtitle="Cada linha representa o que o core conhece hoje sobre a extensao, sem depender do filesystem em tempo de tela." style="margin-top: 20px;">
        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Version</th>
                        <th>Discovery</th>
                        <th>Lifecycle</th>
                        <th>Operational</th>
                        <th>Path</th>
                        <th>Compatibility / Errors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($extensions as $extension)
                        @php($state = $extensionStates[$extension->getKey()] ?? null)
                        <tr>
                            <td>
                                <strong>{{ $state['manifest']['name'] ?? ($extension->name ?? 'Unknown extension') }}</strong><br>
                                <span class="subtle">{{ $state['manifest']['slug'] ?? ($extension->slug ?? 'no-slug') }}</span>
                                @if (($state['manifest']['vendor'] ?? null))
                                    <br><span class="stat-note">Vendor: {{ $state['manifest']['vendor'] }}</span>
                                @endif
                                @if (($state['manifest']['critical'] ?? false))
                                    <br><x-admin.status-badge value="critical" class="status-badge--danger" />
                                @endif
                                @if ($state && ($state['manifest']['is_normalized'] ?? false))
                                    <br><span class="stat-note">Manifest normalized{{ ($state['manifest']['has_warnings'] ?? false) ? ' with warnings' : '' }}.</span>
                                @else
                                    <br><span class="stat-note">Normalized manifest unavailable.</span>
                                @endif
                                @if (!empty($state['health']['status']))
                                    <br><x-admin.status-badge :value="$state['health']['status']" />
                                @endif
                            </td>
                            <td>{{ $extension->type?->value ?? 'unknown' }}</td>
                            <td>{{ $extension->detected_version ?? 'n/a' }}</td>
                            <td><x-admin.status-badge :value="$extension->discovery_status?->value ?? 'unknown'" /></td>
                            <td><x-admin.status-badge :value="$state['lifecycle_status'] ?? 'unknown'" /></td>
                            <td><x-admin.status-badge :value="$extension->operational_status?->value ?? 'unknown'" /></td>
                            <td><code>{{ $extension->path }}</code></td>
                            <td>
                                @if (!empty($extension->discovery_errors))
                                    <div class="stack">
                                        @foreach ($extension->discovery_errors as $error)
                                            <span class="subtle">{{ $error }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="subtle">Compatible with current core constraints.</span>
                                @endif

                                @if (!empty($extension->manifest_warnings))
                                    <div class="stack" style="margin-top: 10px;">
                                        @foreach ($extension->manifest_warnings as $warning)
                                            <span class="stat-note">{{ $warning }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="stack" style="margin-top: 10px;">
                                    @if (!empty($state['manifest']['requires']))
                                        <span class="stat-note">Requires: {{ implode(', ', $state['manifest']['requires']) }}</span>

                                        @php($missingRequirements = $state['dependencies']['missing_requirements'] ?? [])
                                        @php($disabledRequirements = $state['dependencies']['disabled_requirements'] ?? [])

                                        @if (!empty($missingRequirements))
                                            <span class="stat-note">Missing dependencies: {{ implode(', ', array_column($missingRequirements, 'slug')) }}</span>
                                        @endif

                                        @if (!empty($disabledRequirements))
                                            <span class="stat-note">Disabled dependencies: {{ implode(', ', array_column($disabledRequirements, 'slug')) }}</span>
                                        @endif
                                    @else
                                        <span class="stat-note">Requires: none declared.</span>
                                    @endif

                                    @if (!empty($state['capabilities']['recognized_labels']))
                                        <span class="stat-note">Capabilities: {{ implode(', ', $state['capabilities']['recognized_labels']) }}</span>
                                    @else
                                        <span class="stat-note">Capabilities: none recognized.</span>
                                    @endif

                                    @if (!empty($state['capabilities']['custom']))
                                        <span class="stat-note">Custom capabilities: {{ implode(', ', $state['capabilities']['custom']) }}</span>
                                    @endif

                                    @if (!empty($state['capabilities']['warnings']))
                                        @foreach ($state['capabilities']['warnings'] as $warning)
                                            <span class="stat-note">{{ $warning }}</span>
                                        @endforeach
                                    @endif

                                    @if ($extension->type?->value === 'plugin')
                                        @if ($state['migrations']['has_migrations'] ?? false)
                                            <span class="stat-note">
                                                Migrations: {{ count($state['migrations']['migration_files'] ?? []) }} file(s), {{ $state['migrations']['pending_count'] ?? 0 }} pending.
                                            </span>
                                        @elseif ($state['migrations']['has_migrations_directory'] ?? false)
                                            <span class="stat-note">Migrations: directory found, but no migration files were declared.</span>
                                        @else
                                            <span class="stat-note">Migrations: none declared for this plugin.</span>
                                        @endif

                                        @if ($state['settings']['has_catalog'] ?? false)
                                            <span class="stat-note">
                                                Settings: {{ $state['settings']['field_count'] }} field(s) in {{ $state['settings']['group'] }}.
                                            </span>
                                            <span class="stat-note">
                                                Settings permission: {{ $state['settings']['required_permission'] }}{{ ($state['settings']['uses_fallback_permission'] ?? false) ? ' (fallback)' : '' }}.
                                            </span>
                                        @else
                                            <span class="stat-note">Settings: no plugin settings catalog declared.</span>
                                        @endif
                                    @endif

                                    @if (!empty($state['manifest']['provider']))
                                        <span class="stat-note">Provider: {{ $state['manifest']['provider'] }}</span>
                                    @endif

                                    <span class="stat-note">
                                        Lifecycle: {{ $state['is_removed'] ? 'removed from admin registry' : ($state['is_installed'] ? 'installed for admin use' : 'discovered only') }}
                                    </span>

                                    @if (!empty($state['dependencies']['active_dependents']))
                                        <span class="stat-note">Active dependents: {{ implode(', ', array_column($state['dependencies']['active_dependents'], 'slug')) }}</span>
                                    @endif
                                </div>

                                @if (!empty($state['health']['issues']))
                                    <div class="stack" style="margin-top: 10px;">
                                        @foreach ($state['health']['issues'] as $issue)
                                            <span class="stat-note">{{ $issue['message'] }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($canManageExtensions || ($state['can_access_settings_action'] ?? false))
                                    <div class="table-actions">
                                        @if ($canManageExtensions && ($state['can_install_action'] ?? false))
                                            <form method="POST" action="{{ route('admin.extensions.install', $extension) }}">
                                                @csrf
                                                <x-admin.button type="submit">Install</x-admin.button>
                                            </form>
                                        @elseif ($canManageExtensions && ($state['can_disable_action'] ?? false))
                                            <form method="POST" action="{{ route('admin.extensions.disable', $extension) }}">
                                                @csrf
                                                <x-admin.button type="submit" variant="secondary">Disable</x-admin.button>
                                            </form>
                                        @elseif ($canManageExtensions && ($state['can_enable_action'] ?? false))
                                            <form method="POST" action="{{ route('admin.extensions.enable', $extension) }}">
                                                @csrf
                                                <x-admin.button type="submit">Enable</x-admin.button>
                                            </form>
                                        @endif

                                        @if ($canManageExtensions && ($state['can_remove_action'] ?? false))
                                            <form method="POST" action="{{ route('admin.extensions.remove', $extension) }}">
                                                @csrf
                                                <x-admin.button type="submit" variant="secondary">Remove</x-admin.button>
                                            </form>
                                        @endif

                                        @if ($canManageExtensions && ($state['can_run_migrations_action'] ?? false))
                                            <form method="POST" action="{{ route('admin.extensions.migrations.run', $extension) }}">
                                                @csrf
                                                <x-admin.button type="submit" variant="secondary">Run Migrations</x-admin.button>
                                            </form>
                                        @endif

                                    @if ($state['can_access_settings_action'] ?? false)
                                        <x-admin.button href="{{ route('admin.extensions.settings.edit', $extension) }}" variant="secondary">Settings</x-admin.button>
                                    @endif

                                        @if (! (($canManageExtensions && ($state['has_any_action'] ?? false)) || ($state['can_access_settings_action'] ?? false)))
                                            <span class="subtle">Action unavailable.</span>
                                        @endif
                                    </div>

                                    @if ($canManageExtensions && ($state['primary_action'] ?? null) && ! ($state['primary_action']['allowed'] ?? false) && ! empty($state['primary_action']['blocks']))
                                        <div class="stack" style="margin-top: 10px;">
                                            <span class="stat-note">{{ $state['primary_action']['blocks'][0]['message'] }}</span>
                                        </div>
                                    @endif

                                    @if ($canManageExtensions && ($state['primary_action'] ?? null) && ! empty($state['primary_action']['warnings']))
                                        <div class="stack" style="margin-top: 10px;">
                                            @foreach ($state['primary_action']['warnings'] as $warning)
                                                <span class="stat-note">{{ $warning['message'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($canManageExtensions && ($state['remove'] ?? null) && ! ($state['remove']['allowed'] ?? false) && ! empty($state['remove']['blocks']))
                                        <div class="stack" style="margin-top: 10px;">
                                            <span class="stat-note">{{ $state['remove']['blocks'][0]['message'] }}</span>
                                        </div>
                                    @endif

                                    @if ($canManageExtensions && $extension->type?->value === 'plugin' && ($state['migrations'] ?? null) && ! ($state['migrations']['can_run'] ?? false) && (($state['migrations']['pending_count'] ?? 0) > 0 || !empty($state['migrations']['blocks'])))
                                        <div class="stack" style="margin-top: 10px;">
                                            <span class="stat-note">
                                                {{ $state['migrations']['blocks'][0]['message'] ?? $state['migrations']['message'] }}
                                            </span>
                                        </div>
                                    @endif

                                    @if (($state['settings']['warnings'] ?? []) !== [])
                                        <div class="stack" style="margin-top: 10px;">
                                            @foreach ($state['settings']['warnings'] as $warning)
                                                <span class="stat-note">{{ $warning }}</span>
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
                            <td colspan="9">
                                <div class="empty-state">
                                    Nenhuma extensao registrada ainda. O registro sera preenchido pelo fluxo de sincronizacao do core.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($extensions, 'links'))
            <div style="margin-top: 18px;" class="subtle">
                Showing {{ $extensions->firstItem() ?? 0 }}-{{ $extensions->lastItem() ?? 0 }} of {{ $extensions->total() }} registered extensions.
            </div>
        @endif
    </x-admin.glass-card>
</x-layouts.admin>
