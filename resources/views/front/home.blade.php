<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }}</title>
    <style>
        :root {
            --bg: #050b16;
            --panel: rgba(15, 24, 43, 0.76);
            --border: rgba(125, 100, 255, 0.22);
            --text: #edf2ff;
            --muted: #a4b5d4;
            --primary: #8c63ff;
            --accent: #33d1ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
            color: var(--text);
            font-family: "Segoe UI Variable Text", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140, 99, 255, 0.24), transparent 30%),
                radial-gradient(circle at bottom right, rgba(51, 209, 255, 0.18), transparent 26%),
                linear-gradient(180deg, #08101f 0%, var(--bg) 100%);
        }

        .shell {
            width: min(980px, 100%);
            padding: 36px;
            border-radius: 32px;
            background: var(--panel);
            border: 1px solid var(--border);
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.35);
        }

        .eyebrow {
            display: inline-flex;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            color: var(--accent);
            border: 1px solid rgba(51, 209, 255, 0.22);
            background: rgba(51, 209, 255, 0.08);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(2.5rem, 6vw, 4.8rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        p {
            margin: 0;
            max-width: 700px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 1.02rem;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .meta span {
            display: inline-flex;
            padding: 12px 16px;
            border-radius: 18px;
            border: 1px solid rgba(148, 170, 226, 0.14);
            background: rgba(255, 255, 255, 0.03);
            color: var(--muted);
        }
    </style>
</head>
<body>
    <main class="shell">
        <span class="eyebrow">Core Frontend Fallback</span>
        <h1>{{ $siteName }}</h1>
        <p>
            {{ $siteTagline !== '' ? $siteTagline : 'O frontend esta usando a view padrao do core porque nenhum tema ativo forneceu um template dedicado para esta rota.' }}
        </p>

        <div class="meta">
            <span>Theme: {{ $activeTheme['slug'] ?? 'core-fallback' }}</span>
            <span>Locale: {{ app()->getLocale() }}</span>
            <span>Timezone: {{ config('app.timezone') }}</span>
        </div>

        @if ($footerText !== '')
            <p style="margin-top: 24px;">{{ $footerText }}</p>
        @endif
    </main>
</body>
</html>
