<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" eyebrow="Plugin Settings">
        <x-admin.button href="{{ route('admin.extensions.index') }}" variant="secondary">Back To Extensions</x-admin.button>
        @if ($canManageSettings)
            <span class="status-badge status-badge--success">Editable</span>
        @else
            <span class="status-badge status-badge--neutral">Read Only</span>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.extensions.settings.update', $extension) }}">
        @csrf
        @method('PUT')

        <div class="grid grid--two">
            <x-admin.glass-card :title="$catalog->pluginName().' Catalog'" subtitle="Settings declarados de forma explicita pelo plugin e persistidos pelo core sob escopo isolado.">
                <div class="form-grid">
                    @foreach ($fields as $key => $field)
                        <div class="field">
                            <label for="{{ $key }}">{{ $field['label'] }}</label>

                            @if (($field['input'] ?? 'text') === 'textarea')
                                <textarea
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    {{ $canManageSettings ? '' : 'readonly' }}
                                >{{ old($key, $values[$key] ?? $field['default'] ?? '') }}</textarea>
                            @elseif (($field['input'] ?? 'text') === 'checkbox')
                                <label class="checkbox-field" for="{{ $key }}">
                                    <input
                                        id="{{ $key }}"
                                        name="{{ $key }}"
                                        type="hidden"
                                        value="0"
                                    >
                                    <input
                                        id="{{ $key }}_toggle"
                                        name="{{ $key }}"
                                        type="checkbox"
                                        value="1"
                                        @checked((bool) old($key, $values[$key] ?? $field['default'] ?? false))
                                        {{ $canManageSettings ? '' : 'disabled' }}
                                    >
                                    <span>{{ $field['description'] }}</span>
                                </label>
                            @else
                                <input
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    type="{{ $field['input'] ?? 'text' }}"
                                    value="{{ old($key, $values[$key] ?? $field['default'] ?? '') }}"
                                    {{ $canManageSettings ? '' : 'readonly' }}
                                >
                            @endif

                            @if (($field['input'] ?? 'text') !== 'checkbox')
                                <span class="stat-note">{{ $field['description'] }}</span>
                            @endif

                            @error($key)
                                <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </x-admin.glass-card>

            <div class="stack">
                <x-admin.glass-card title="Operational Notes" subtitle="Como o core governa os settings de plugin nesta fase.">
                    <div class="stack">
                        <div class="notice">
                            Este catalogo so fica acessivel enquanto o plugin estiver <strong>valid</strong> e <strong>installed</strong>. Nao exigimos <strong>enabled</strong> para permitir preconfiguracao segura.
                        </div>
                        <div class="notice">
                            O grupo persistido atual e <code>{{ $catalog->groupName() }}</code>, separado dos settings globais do core e dos settings de tema.
                        </div>
                        <div class="notice">
                            Permissao exigida: <code>{{ $catalog->resolvedPermission() }}</code>{{ $catalog->usesFallbackPermission() ? ' (fallback operacional para manage_extensions).' : '.' }}
                        </div>
                    </div>
                </x-admin.glass-card>

                <x-admin.glass-card title="Current Snapshot" subtitle="Valores resolvidos atualmente para este plugin.">
                    <div class="key-value">
                        @foreach ($values as $key => $value)
                            <div class="key-value-item">
                                <span>{{ $fields[$key]['label'] ?? $key }}</span>
                                <strong>
                                    @if (is_bool($value))
                                        {{ $value ? 'true' : 'false' }}
                                    @else
                                        {{ $value !== '' && $value !== null ? \Illuminate\Support\Str::limit((string) $value, 100) : 'n/a' }}
                                    @endif
                                </strong>
                            </div>
                        @endforeach
                    </div>

                    @if ($catalog->warnings() !== [])
                        <div class="stack" style="margin-top: 16px;">
                            @foreach ($catalog->warnings() as $warning)
                                <span class="stat-note">{{ $warning }}</span>
                            @endforeach
                        </div>
                    @endif
                </x-admin.glass-card>
            </div>
        </div>

        @if ($canManageSettings)
            <div class="actions-row" style="margin-top: 20px;">
                <button type="submit" class="admin-button admin-button--primary">Save Plugin Settings</button>
            </div>
        @endif
    </form>
</x-layouts.admin>
