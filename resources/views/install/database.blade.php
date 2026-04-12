<x-layouts.installer :page-title="$pageTitle" :steps="$steps" :current-step="$currentStep">
    <div class="page-header">
        <div>
            <span class="page-eyebrow">Database</span>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageSubtitle }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('install.database.store') }}" class="stack">
        @csrf

        <div class="grid-two">
            <div class="field">
                <label for="driver">Driver</label>
                <select id="driver" name="driver">
                    @foreach (['sqlite' => 'SQLite', 'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('driver', $database['driver']) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="database">Database</label>
                <input id="database" name="database" type="text" value="{{ old('database', $database['database']) }}" required>
            </div>
        </div>

        <div class="grid-two">
            <div class="field">
                <label for="host">Host</label>
                <input id="host" name="host" type="text" value="{{ old('host', $database['host']) }}">
            </div>

            <div class="field">
                <label for="port">Port</label>
                <input id="port" name="port" type="text" value="{{ old('port', $database['port']) }}">
            </div>
        </div>

        <div class="grid-two">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', $database['username']) }}">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" value="{{ old('password', $database['password']) }}">
            </div>
        </div>

        <div class="notice">
            Para SQLite, informe um caminho de arquivo como <code>database/database.sqlite</code>. Para MySQL ou PostgreSQL, preencha host, porta e credenciais.
        </div>

        <div class="actions">
            <a class="admin-button admin-button--secondary" href="{{ route('install.welcome') }}">Back</a>
            <button class="admin-button admin-button--primary" type="submit">Save Database Step</button>
        </div>
    </form>
</x-layouts.installer>
