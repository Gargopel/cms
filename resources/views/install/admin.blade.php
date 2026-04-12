<x-layouts.installer :page-title="$pageTitle" :steps="$steps" :current-step="$currentStep">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">Administrator</span>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('install.admin.store') }}" class="stack">
        @csrf

        <div class="grid-two">
            <div class="field">
                <label for="app_name">Application name</label>
                <input id="app_name" name="app_name" type="text" value="{{ old('app_name', $administrator['app_name']) }}" required>
            </div>

            <div class="field">
                <label for="app_url">Application URL</label>
                <input id="app_url" name="app_url" type="url" value="{{ old('app_url', $administrator['app_url']) }}" required>
            </div>
        </div>

        <div class="grid-two">
            <div class="field">
                <label for="name">Administrator name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $administrator['name']) }}" required>
            </div>

            <div class="field">
                <label for="email">Administrator email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $administrator['email']) }}" required>
            </div>
        </div>

        <div class="grid-two">
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
        </div>

        <div class="actions">
            <a class="admin-button admin-button--secondary" href="{{ route('install.database') }}">Back</a>
            <button class="admin-button admin-button--primary" type="submit">Save Administrator Step</button>
        </div>
    </form>

    <form method="POST" action="{{ route('install.run') }}" class="stack" style="margin-top: 24px;">
        @csrf

        <div class="notice">
            Quando voce iniciar o setup, o instalador vai testar o banco, gravar a configuracao inicial, gerar a app key, rodar migrations, sincronizar seguranca do core e criar o administrador informado.
        </div>

        <div class="actions">
            <button class="admin-button admin-button--primary" type="submit">Run Installation</button>
            <a class="admin-button admin-button--secondary" href="{{ route('install.requirements') }}">Review Requirements Again</a>
        </div>
    </form>
</x-layouts.installer>
