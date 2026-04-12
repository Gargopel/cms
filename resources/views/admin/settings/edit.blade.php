<x-layouts.admin>
    <x-admin.page-header :title="$pageTitle" :subtitle="$pageSubtitle" eyebrow="Core Settings">
        @if ($canManageSettings)
            <span class="status-badge status-badge--success">Editable</span>
        @else
            <span class="status-badge status-badge--neutral">Read Only</span>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid--two">
            <x-admin.glass-card title="General Settings" subtitle="Base operacional minima do produto, mantida no core e pronta para expansao futura por plugins e temas.">
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
                            @else
                                <input
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    type="{{ $field['input'] ?? 'text' }}"
                                    value="{{ old($key, $values[$key] ?? $field['default'] ?? '') }}"
                                    {{ $canManageSettings ? '' : 'readonly' }}
                                >
                            @endif

                            <span class="stat-note">{{ $field['description'] }}</span>

                            @error($key)
                                <span class="stat-note" style="color: var(--danger);">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </x-admin.glass-card>

            <div class="stack">
                <x-admin.glass-card title="Runtime Notes" subtitle="Como o core usa essas configuracoes nesta fase.">
                    <div class="stack">
                        <div class="notice">
                            site name, locale e timezone sao aplicados de forma centralizada no runtime quando existirem no registro persistido.
                        </div>
                        <div class="notice">
                            system email prepara a base para notificacoes e integracoes futuras do core sem exigir um modulo de configuracao grande agora.
                        </div>
                        <div class="notice">
                            global scripts fica apenas armazenado nesta etapa. O core ainda nao injeta esse conteudo automaticamente para evitar comportamento inseguro ou magico.
                        </div>
                    </div>
                </x-admin.glass-card>

                <x-admin.glass-card title="Current Snapshot" subtitle="Valores resolvidos atualmente pelo core para o grupo general.">
                    <div class="key-value">
                        @foreach ($values as $key => $value)
                            <div class="key-value-item">
                                <span>{{ $fields[$key]['label'] ?? $key }}</span>
                                <strong>{{ $value !== '' && $value !== null ? \Illuminate\Support\Str::limit((string) $value, 80) : 'n/a' }}</strong>
                            </div>
                        @endforeach
                    </div>
                </x-admin.glass-card>
            </div>
        </div>

        @if ($canManageSettings)
            <div class="actions-row" style="margin-top: 20px;">
                <button type="submit" class="admin-button admin-button--primary">Save Settings</button>
            </div>
        @endif
    </form>
</x-layouts.admin>
