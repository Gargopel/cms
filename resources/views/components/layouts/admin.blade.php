<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Core Admin' }} - {{ config('platform.core.name') }}</title>
    <style>
        :root {
            --bg: #07111f;
            --bg-deep: #040913;
            --text: #edf2ff;
            --muted: #9eb0ce;
            --muted-strong: #c8d6f0;
            --primary: #8c63ff;
            --accent: #33d1ff;
            --success: #2fe6ae;
            --warning: #ffb85c;
            --danger: #ff6d8a;
            --panel-border: rgba(113, 91, 255, 0.26);
            --shadow: 0 24px 80px rgba(1, 7, 18, 0.45);
            --font-display: "Segoe UI Variable Display", "Trebuchet MS", "Gill Sans", sans-serif;
            --font-body: "Segoe UI Variable Text", "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: var(--font-body);
            background:
                radial-gradient(circle at top left, rgba(140, 99, 255, 0.24), transparent 28%),
                radial-gradient(circle at top right, rgba(51, 209, 255, 0.18), transparent 24%),
                radial-gradient(circle at bottom center, rgba(40, 226, 174, 0.09), transparent 24%),
                linear-gradient(180deg, #091221 0%, var(--bg-deep) 100%);
        }

        a { color: inherit; text-decoration: none; }

        .admin-shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }

        .admin-sidebar {
            position: relative;
            padding: 28px 20px;
            background: linear-gradient(180deg, rgba(7, 16, 31, 0.96), rgba(5, 10, 20, 0.84));
            border-right: 1px solid rgba(128, 153, 222, 0.08);
        }

        .admin-sidebar::after {
            content: "";
            position: absolute;
            inset: 24px 18px;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
            border: 1px solid rgba(125, 100, 255, 0.14);
            pointer-events: none;
        }

        .sidebar-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 28px;
        }

        .brand-chip,
        .sidebar-footnote {
            padding: 18px 18px 16px;
            border-radius: 24px;
            background: rgba(14, 24, 45, 0.72);
            border: 1px solid rgba(125, 100, 255, 0.18);
        }

        .brand-chip span,
        .page-eyebrow {
            display: inline-flex;
            font-size: 0.74rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .brand-chip span { margin-bottom: 10px; }

        .brand-chip strong {
            display: block;
            font-family: var(--font-display);
            font-size: 1.28rem;
            line-height: 1.1;
        }

        .brand-chip p,
        .sidebar-footnote,
        .page-header p,
        .glass-card-header p,
        .subtle,
        .footer-note {
            color: var(--muted);
            line-height: 1.6;
        }

        .sidebar-nav {
            display: grid;
            gap: 10px;
        }

        .sidebar-group-label {
            padding: 8px 8px 0;
            color: var(--accent);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.02);
            color: var(--muted-strong);
            border: 1px solid transparent;
        }

        .nav-link.is-active,
        .nav-link:hover {
            background: rgba(87, 64, 192, 0.26);
            border-color: rgba(131, 108, 255, 0.28);
        }

        .nav-link small {
            color: var(--muted);
            font-size: 0.78rem;
            display: block;
            margin-top: 4px;
        }

        .sidebar-footnote {
            margin-top: auto;
            font-size: 0.9rem;
        }

        .sidebar-user {
            padding: 16px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .sidebar-user strong {
            display: block;
            color: var(--muted-strong);
        }

        .admin-main { padding: 32px; }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }

        .page-eyebrow {
            margin-bottom: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(51, 209, 255, 0.1);
            border: 1px solid rgba(51, 209, 255, 0.2);
        }

        .page-header h1 {
            margin: 0;
            font-size: clamp(2rem, 3.5vw, 3rem);
            line-height: 1;
            font-family: var(--font-display);
            letter-spacing: -0.03em;
        }

        .grid {
            display: grid;
            gap: 20px;
        }

        .grid--metrics { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .grid--two { grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr); }
        .grid--three { grid-template-columns: repeat(3, minmax(0, 1fr)); }

        .glass-card {
            position: relative;
            background: linear-gradient(180deg, rgba(15, 28, 51, 0.82), rgba(10, 19, 36, 0.88));
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .glass-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), transparent 30%);
            pointer-events: none;
        }

        .glass-card--padded { padding: 22px; }
        .glass-card-header { margin-bottom: 18px; }

        .glass-card-header h2,
        .metric-card strong,
        .mini-stat strong {
            font-family: var(--font-display);
        }

        .glass-card-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .metric-card {
            display: grid;
            gap: 10px;
        }

        .metric-card span,
        .mini-stat span,
        th,
        .key-value-item span {
            color: var(--muted);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .metric-card strong {
            font-size: 2rem;
            line-height: 1;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .status-badge--success {
            color: #d9fff2;
            background: rgba(47, 230, 174, 0.12);
            border-color: rgba(47, 230, 174, 0.24);
        }

        .status-badge--warning {
            color: #fff0d5;
            background: rgba(255, 184, 92, 0.14);
            border-color: rgba(255, 184, 92, 0.26);
        }

        .status-badge--danger {
            color: #ffe1e8;
            background: rgba(255, 109, 138, 0.14);
            border-color: rgba(255, 109, 138, 0.26);
        }

        .status-badge--neutral {
            color: #dce7ff;
            background: rgba(126, 149, 198, 0.12);
            border-color: rgba(126, 149, 198, 0.24);
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .admin-button--primary {
            color: #f7f8ff;
            background: linear-gradient(135deg, var(--primary) 0%, #4562ff 100%);
        }

        .admin-button--secondary {
            color: var(--muted-strong);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(149, 171, 225, 0.18);
        }

        .admin-button--danger {
            color: #fff1f4;
            background: rgba(255, 109, 138, 0.14);
            border: 1px solid rgba(255, 109, 138, 0.26);
        }

        .table-shell {
            overflow-x: auto;
            border-radius: 20px;
            border: 1px solid rgba(137, 155, 213, 0.12);
            background: rgba(7, 14, 29, 0.48);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 880px;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid rgba(149, 171, 225, 0.08);
            vertical-align: top;
        }

        td {
            color: var(--muted-strong);
            font-size: 0.95rem;
        }

        td code {
            color: #dce7ff;
            font-size: 0.85rem;
            word-break: break-all;
        }

        .stack { display: grid; gap: 14px; }
        .list-inline, .actions-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .form-grid { display: grid; gap: 16px; }
        .form-grid--two { grid-template-columns: repeat(2, minmax(0, 1fr)); }

        .field {
            display: grid;
            gap: 8px;
        }

        .field label {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            min-height: 48px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(149, 171, 225, 0.18);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: inherit;
        }

        .field textarea {
            min-height: 132px;
            padding: 14px 16px;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: rgba(140, 99, 255, 0.65);
            box-shadow: 0 0 0 3px rgba(140, 99, 255, 0.18);
        }

        .checkbox-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .checkbox-card {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .checkbox-card input {
            margin-top: 4px;
        }

        .checkbox-card strong {
            display: block;
            color: var(--muted-strong);
        }

        .stat-note {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .table-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .mini-stat,
        .key-value-item,
        .empty-state,
        .notice,
        .status-message {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .mini-stat strong {
            display: block;
            font-size: 1.35rem;
        }

        .notice {
            color: var(--muted-strong);
        }

        .status-message {
            margin-bottom: 18px;
            color: #d8fff2;
            background: rgba(47, 230, 174, 0.1);
            border-color: rgba(47, 230, 174, 0.22);
        }

        .error-message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            color: #ffe1e8;
            background: rgba(255, 109, 138, 0.12);
            border: 1px solid rgba(255, 109, 138, 0.24);
        }

        .key-value {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .key-value-item strong {
            display: block;
            font-size: 1rem;
            color: var(--muted-strong);
            word-break: break-word;
        }

        @media (max-width: 1120px) {
            .admin-shell,
            .grid--metrics,
            .grid--two,
            .grid--three,
            .key-value {
                grid-template-columns: 1fr;
            }

            .form-grid--two,
            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                padding: 20px;
                border-right: 0;
                border-bottom: 1px solid rgba(128, 153, 222, 0.08);
            }

            .admin-main { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="sidebar-inner">
                <div class="brand-chip">
                    <span>Platform Core</span>
                    <strong>{{ config('platform.core.name') }}</strong>
                    <p>Admin operacional minimo para leitura do estado do core, extensoes e manutencao segura.</p>
                </div>

                <nav class="sidebar-nav">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <div><strong>Dashboard</strong><small>Visao geral</small></div>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.extensions.*') ? 'is-active' : '' }}" href="{{ route('admin.extensions.index') }}">
                        <div><strong>Extensions</strong><small>Registro e operacao</small></div>
                    </a>
                    @can('view_themes')
                        <a class="nav-link {{ request()->routeIs('admin.themes.*') ? 'is-active' : '' }}" href="{{ route('admin.themes.index') }}">
                            <div><strong>Themes</strong><small>Tema ativo e frontend</small></div>
                        </a>
                    @endcan
                    @can('view_media')
                        <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}" href="{{ route('admin.media.index') }}">
                            <div><strong>Media</strong><small>Upload e biblioteca</small></div>
                        </a>
                    @endcan
                    <a class="nav-link {{ request()->routeIs('admin.maintenance') || request()->routeIs('admin.maintenance.*') ? 'is-active' : '' }}" href="{{ route('admin.maintenance') }}">
                        <div><strong>Maintenance</strong><small>Operacao segura</small></div>
                    </a>
                    @can('manage_users')
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                            <div><strong>Users</strong><small>Governanca de acesso</small></div>
                        </a>
                    @endcan
                    @can('manage_roles')
                        <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}">
                            <div><strong>Roles</strong><small>Cargos e atribuicoes</small></div>
                        </a>
                    @endcan
                    @can('manage_permissions')
                        <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'is-active' : '' }}" href="{{ route('admin.permissions.index') }}">
                            <div><strong>Permissions</strong><small>Catalogo do core</small></div>
                        </a>
                    @endcan
                    @can('view_settings')
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.edit') }}">
                            <div><strong>Settings</strong><small>Configuracao global</small></div>
                        </a>
                    @endcan
                    @can('view_audit_logs')
                        <a class="nav-link {{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}" href="{{ route('admin.audit.index') }}">
                            <div><strong>Audit Logs</strong><small>Acoes sensiveis</small></div>
                        </a>
                    @endcan
                    @can('view_system_health')
                        <a class="nav-link {{ request()->routeIs('admin.health.*') ? 'is-active' : '' }}" href="{{ route('admin.health.index') }}">
                            <div><strong>System Health</strong><small>Diagnostico basico</small></div>
                        </a>
                    @endcan
                    @if (($extensionNavigationItems ?? []) !== [])
                        <div class="sidebar-group-label">Plugin Surfaces</div>
                        @foreach ($extensionNavigationItems as $item)
                            <a class="nav-link {{ $item['active'] ? 'is-active' : '' }}" href="{{ $item['href'] }}">
                                <div>
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['description'] }}</small>
                                </div>
                            </a>
                        @endforeach
                    @endif
                </nav>

                @if (auth()->check())
                    <div class="sidebar-user">
                        <span class="subtle">Authenticated as</span>
                        <strong>{{ auth()->user()->name }}</strong>
                        <span class="subtle">{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}" style="margin-top: 14px;">
                            @csrf
                            <button type="submit" class="admin-button admin-button--danger" style="width: 100%;">Sign Out</button>
                        </form>
                    </div>
                @endif

                <div class="sidebar-footnote">
                    Prefixo atual: <strong>/{{ trim((string) config('platform.admin.prefix', 'admin'), '/') }}</strong><br>
                    Ambiente: <strong>{{ app()->environment() }}</strong><br>
                    Core: <strong>{{ config('platform.core.version') }}</strong>
                </div>
            </div>
        </aside>

        <main class="admin-main">
            @if (session('status'))
                <div class="status-message">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-message">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
