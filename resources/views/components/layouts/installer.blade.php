@props([
    'pageTitle' => 'Installer',
    'steps' => [],
    'currentStep' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Installer' }} - {{ config('platform.core.name') }}</title>
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

        .installer-shell {
            width: min(1160px, calc(100% - 40px));
            margin: 0 auto;
            padding: 32px 0 40px;
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
            gap: 24px;
        }

        .installer-sidebar,
        .glass-card {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            border: 1px solid var(--panel-border);
            background: linear-gradient(180deg, rgba(15, 28, 51, 0.82), rgba(10, 19, 36, 0.88));
            box-shadow: var(--shadow);
        }

        .installer-sidebar::before,
        .glass-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), transparent 30%);
            pointer-events: none;
        }

        .sidebar-inner,
        .card-inner {
            position: relative;
            z-index: 1;
            padding: 28px;
        }

        .brand-kicker,
        .page-eyebrow {
            display: inline-flex;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.74rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.1);
            border: 1px solid rgba(51, 209, 255, 0.18);
        }

        h1,
        h2,
        h3 {
            margin: 0;
            font-family: var(--font-display);
            letter-spacing: -0.03em;
        }

        .sidebar-title {
            font-size: 2.1rem;
            line-height: 0.96;
            margin: 16px 0 12px;
        }

        .subtle,
        p,
        label {
            color: var(--muted);
            line-height: 1.7;
        }

        .steps {
            display: grid;
            gap: 12px;
            margin-top: 26px;
        }

        .step-item {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .step-item.is-current {
            background: rgba(87, 64, 192, 0.26);
            border-color: rgba(131, 108, 255, 0.28);
        }

        .step-item strong {
            display: block;
            color: var(--muted-strong);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-header h1 {
            font-size: clamp(2rem, 3.5vw, 3rem);
            line-height: 1;
            margin-top: 14px;
        }

        .stack { display: grid; gap: 18px; }
        .grid-two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }

        .field {
            display: grid;
            gap: 8px;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 50px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(149, 171, 225, 0.18);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: inherit;
        }

        .field input:focus,
        .field select:focus {
            outline: none;
            border-color: rgba(140, 99, 255, 0.65);
            box-shadow: 0 0 0 3px rgba(140, 99, 255, 0.18);
        }

        .admin-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 14px;
            border: 0;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
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

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .status-list {
            display: grid;
            gap: 12px;
        }

        .status-item {
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(149, 171, 225, 0.12);
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 86px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            text-transform: uppercase;
        }

        .status-pill--success {
            color: #d9fff2;
            background: rgba(47, 230, 174, 0.12);
            border: 1px solid rgba(47, 230, 174, 0.24);
        }

        .status-pill--danger {
            color: #ffe1e8;
            background: rgba(255, 109, 138, 0.14);
            border: 1px solid rgba(255, 109, 138, 0.26);
        }

        .notice,
        .error-box {
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .notice {
            background: rgba(255, 255, 255, 0.03);
        }

        .error-box {
            color: #ffe1e8;
            background: rgba(255, 109, 138, 0.14);
            border-color: rgba(255, 109, 138, 0.26);
        }

        .metric-list {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .metric-item {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .metric-item strong {
            display: block;
            font-size: 1.2rem;
            color: var(--muted-strong);
        }

        @media (max-width: 980px) {
            .installer-shell,
            .grid-two,
            .metric-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="installer-shell">
        <aside class="installer-sidebar">
            <div class="sidebar-inner">
                <span class="brand-kicker">Web Installer</span>
                <h2 class="sidebar-title">{{ config('platform.core.name') }}</h2>
                <p>Fluxo guiado para preparar configuracao inicial, banco e administrador do produto sem depender de CLI do usuario final.</p>

                <div class="steps">
                    @foreach ($steps as $slug => $label)
                        <div class="step-item {{ $currentStep === $slug ? 'is-current' : '' }}">
                            <strong>{{ $label }}</strong>
                            <span class="subtle">Step: {{ strtoupper($slug) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        <main class="glass-card">
            <div class="card-inner">
                @if ($errors->any())
                    <div class="error-box" style="margin-bottom: 18px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
