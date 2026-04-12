<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Admin Login' }} - {{ config('platform.core.name') }}</title>
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
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }

        .auth-shell {
            width: min(100%, 980px);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, 0.9fr);
            gap: 24px;
        }

        .glass-panel {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            border: 1px solid var(--panel-border);
            background: linear-gradient(180deg, rgba(15, 28, 51, 0.82), rgba(10, 19, 36, 0.88));
            box-shadow: var(--shadow);
        }

        .glass-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), transparent 30%);
            pointer-events: none;
        }

        .brand-panel,
        .form-panel {
            position: relative;
            z-index: 1;
            padding: 30px;
        }

        .brand-kicker {
            display: inline-flex;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.74rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.1);
            border: 1px solid rgba(51, 209, 255, 0.18);
            margin-bottom: 18px;
        }

        h1,
        h2 {
            margin: 0;
            font-family: var(--font-display);
            letter-spacing: -0.03em;
        }

        h1 {
            font-size: clamp(2.4rem, 4vw, 3.6rem);
            line-height: 0.95;
            margin-bottom: 16px;
        }

        p,
        label,
        .subtle {
            color: var(--muted);
            line-height: 1.7;
        }

        .brand-points {
            display: grid;
            gap: 14px;
            margin-top: 26px;
        }

        .brand-point {
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(149, 171, 225, 0.1);
        }

        .brand-point strong {
            display: block;
            margin-bottom: 6px;
            color: var(--muted-strong);
        }

        .form-panel h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .field {
            display: grid;
            gap: 8px;
            margin-top: 18px;
        }

        .field input {
            width: 100%;
            min-height: 50px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(149, 171, 225, 0.18);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: inherit;
        }

        .field input:focus {
            outline: none;
            border-color: rgba(140, 99, 255, 0.65);
            box-shadow: 0 0 0 3px rgba(140, 99, 255, 0.18);
        }

        .error-box,
        .hint-box {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(149, 171, 225, 0.12);
        }

        .error-box {
            color: #ffe1e8;
            background: rgba(255, 109, 138, 0.12);
            border-color: rgba(255, 109, 138, 0.22);
        }

        .hint-box {
            background: rgba(255, 255, 255, 0.03);
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 18px;
        }

        .remember-row input {
            width: 18px;
            height: 18px;
        }

        .submit-button {
            width: 100%;
            min-height: 50px;
            margin-top: 22px;
            border: 0;
            border-radius: 16px;
            cursor: pointer;
            color: #f7f8ff;
            font: inherit;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary) 0%, #4562ff 100%);
        }

        @media (max-width: 920px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    {{ $slot }}
</body>
</html>
