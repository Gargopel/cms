<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }}</title>
    <style>
        :root {
            --bg: #030712;
            --text: #f4f7ff;
            --muted: #a8b7d6;
            --primary: #8c63ff;
            --accent: #33d1ff;
            --surface: rgba(10, 18, 34, 0.78);
            --border: rgba(140, 99, 255, 0.2);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: "Segoe UI Variable Text", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140, 99, 255, 0.3), transparent 30%),
                radial-gradient(circle at top right, rgba(51, 209, 255, 0.16), transparent 24%),
                linear-gradient(180deg, #07101f 0%, var(--bg) 100%);
        }

        .hero {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 56px 28px;
        }

        .panel {
            padding: 40px;
            border-radius: 36px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 34px 120px rgba(0, 0, 0, 0.34);
        }

        .eyebrow {
            display: inline-flex;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.09);
            border: 1px solid rgba(51, 209, 255, 0.18);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.76rem;
        }

        h1 {
            margin: 0 0 16px;
            font-size: clamp(3rem, 7vw, 5.8rem);
            line-height: 0.92;
            letter-spacing: -0.06em;
        }

        p {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            line-height: 1.8;
            font-size: 1.03rem;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 34px;
        }

        .meta-item {
            padding: 16px 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(148, 170, 226, 0.12);
        }

        .meta-item span {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 0.76rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        @media (max-width: 900px) {
            .meta {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 28px;
            }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="panel">
            <span class="eyebrow">Example Theme Active</span>
            <h1>{{ $siteName }}</h1>
            <p>{{ $siteTagline !== '' ? $siteTagline : 'O frontend agora esta sendo renderizado por um tema ativo do core, com fallback seguro para o core quando uma view nao existir.' }}</p>

            <div class="meta">
                <div class="meta-item">
                    <span>Theme</span>
                    <strong>{{ $activeTheme['slug'] ?? 'example-theme' }}</strong>
                </div>
                <div class="meta-item">
                    <span>Locale</span>
                    <strong>{{ app()->getLocale() }}</strong>
                </div>
                <div class="meta-item">
                    <span>Footer</span>
                    <strong>{{ $footerText !== '' ? $footerText : 'Configured by core settings' }}</strong>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
