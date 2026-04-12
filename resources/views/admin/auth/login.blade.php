<x-layouts.admin-auth>
    <div class="auth-shell">
        <section class="glass-panel">
            <div class="brand-panel">
                <span class="brand-kicker">Platform Core</span>
                <h1>{{ config('platform.core.name') }}</h1>
                <p>{{ $pageSubtitle }}</p>

                <div class="brand-points">
                    <div class="brand-point">
                        <strong>Auth do core</strong>
                        <span>O acesso ao admin agora exige autenticacao por sessao e autorizacao por permissoes.</span>
                    </div>
                    <div class="brand-point">
                        <strong>Papeis e permissoes</strong>
                        <span>A base atual suporta extensao futura por plugins sem depender de pacote pesado.</span>
                    </div>
                    <div class="brand-point">
                        <strong>Etapa incremental</strong>
                        <span>O objetivo aqui e governar o painel do core com seguranca, sem criar IAM enterprise.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="glass-panel">
            <div class="form-panel">
                <span class="brand-kicker">Admin Login</span>
                <h2>{{ $pageTitle }}</h2>
                <p>Entre com um usuario que possua acesso administrativo ao core.</p>

                @if ($errors->any())
                    <div class="error-box">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}">
                    @csrf

                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" required>
                    </div>

                    <label class="remember-row" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        <span class="subtle">Keep this admin session active on this browser.</span>
                    </label>

                    <button class="submit-button" type="submit">Enter Admin</button>
                </form>

                @if (app()->environment(['local', 'testing']) && config('platform.admin.seed_local_admin'))
                    <div class="hint-box">
                        <strong>Local bootstrap</strong><br>
                        <span class="subtle">
                            Usuario local padrao: {{ config('platform.admin.local_admin_email') }}.
                            Ajuste as credenciais por ambiente e evite esse fluxo em producao.
                        </span>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-layouts.admin-auth>
