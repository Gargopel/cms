<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (isset($seo) && $seo && \Illuminate\Support\Facades\View::exists('seo::partials.meta'))
        @include('seo::partials.meta', ['seo' => $seo])
    @else
        <title>{{ $page->title }} - {{ config('app.name') }}</title>
    @endif
    <style>
        :root {
            --bg: #07111f;
            --bg-deep: #040913;
            --text: #edf2ff;
            --muted: #9eb0ce;
            --primary: #8c63ff;
            --accent: #33d1ff;
            --panel-border: rgba(113, 91, 255, 0.26);
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: "Segoe UI Variable Text", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(140, 99, 255, 0.24), transparent 28%),
                radial-gradient(circle at top right, rgba(51, 209, 255, 0.18), transparent 24%),
                linear-gradient(180deg, #091221 0%, var(--bg-deep) 100%);
        }

        .page-shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 72px 24px;
        }

        .page-card {
            padding: 36px;
            border-radius: 28px;
            border: 1px solid var(--panel-border);
            background: linear-gradient(180deg, rgba(15, 28, 51, 0.82), rgba(10, 19, 36, 0.88));
            box-shadow: 0 24px 80px rgba(1, 7, 18, 0.45);
        }

        .eyebrow {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.1);
            border: 1px solid rgba(51, 209, 255, 0.2);
            font-size: 0.78rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 10px;
            font-size: clamp(2rem, 5vw, 3.4rem);
            line-height: 1;
            font-family: "Segoe UI Variable Display", "Trebuchet MS", sans-serif;
        }

        .meta {
            color: var(--muted);
            margin-bottom: 28px;
        }

        .content {
            color: var(--text);
            line-height: 1.9;
            font-size: 1.04rem;
        }

        .featured-image {
            width: 100%;
            border-radius: 24px;
            margin: 0 0 24px;
            display: block;
            border: 1px solid var(--panel-border);
        }
    </style>
</head>
<body>
    <main class="page-shell">
        <article class="page-card">
            <div class="eyebrow">Pages Plugin</div>
            <h1>{{ $page->title }}</h1>
            <div class="meta">Slug: {{ $page->slug }} | Site: {{ config('app.name') }}</div>
            @if ($page->featuredImage?->url())
                <img class="featured-image" src="{{ $page->featuredImage->url() }}" alt="{{ $page->title }}">
            @endif
            <div class="content">{!! nl2br(e($page->content)) !!}</div>
        </article>
    </main>
</body>
</html>
