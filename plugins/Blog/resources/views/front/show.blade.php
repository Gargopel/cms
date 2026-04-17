<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (isset($seo) && $seo && \Illuminate\Support\Facades\View::exists('seo::partials.meta'))
        @include('seo::partials.meta', ['seo' => $seo])
    @else
        <title>{{ $post->title }} - {{ config('app.name') }}</title>
    @endif
    <style>
        :root {
            --bg: #07111f;
            --bg-deep: #040913;
            --text: #edf2ff;
            --muted: #9eb0ce;
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

        .shell {
            max-width: 880px;
            margin: 0 auto;
            padding: 72px 24px;
        }

        .card {
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
            margin-bottom: 18px;
        }

        .category-pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.08);
            border: 1px solid rgba(51, 209, 255, 0.18);
            margin: 0 0 18px;
            font-size: 0.82rem;
            text-decoration: none;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 18px;
        }

        .tag-pill {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            color: var(--text);
            background: rgba(113, 91, 255, 0.16);
            border: 1px solid rgba(113, 91, 255, 0.26);
            font-size: 0.78rem;
            text-decoration: none;
        }

        .excerpt {
            color: var(--accent);
            margin-bottom: 24px;
            font-size: 1.05rem;
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
    <main class="shell">
        <article class="card">
            <div class="eyebrow">Blog Plugin</div>
            <h1>{{ $post->title }}</h1>
            <div class="meta">Published: {{ $post->published_at?->format('Y-m-d H:i') ?? 'n/a' }} | Slug: {{ $post->slug }}</div>
            @if ($post->category)
                <a href="{{ url('/blog/category/'.$post->category->slug) }}" class="category-pill">{{ $post->category->name }}</a>
            @endif
            @if ($post->tags->isNotEmpty())
                <div class="tag-row">
                    @foreach ($post->tags as $tag)
                        <a href="{{ url('/blog/tag/'.$tag->slug) }}" class="tag-pill">{{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
            @if ($post->featuredImage?->url())
                <img class="featured-image" src="{{ $post->featuredImage->url() }}" alt="{{ $post->title }}">
            @endif
            <div class="excerpt">{{ $post->excerpt }}</div>
            <div class="content">{!! nl2br(e($post->content)) !!}</div>
        </article>
    </main>
</body>
</html>
