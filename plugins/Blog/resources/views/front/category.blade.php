<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (isset($seo) && $seo && \Illuminate\Support\Facades\View::exists('seo::partials.meta'))
        @include('seo::partials.meta', ['seo' => $seo])
    @else
        <title>{{ $category->name }} - {{ $blogTitle }} - {{ config('app.name') }}</title>
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
            max-width: 980px;
            margin: 0 auto;
            padding: 72px 24px;
        }

        .hero {
            margin-bottom: 28px;
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

        .subtle {
            color: var(--muted);
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .card {
            padding: 28px;
            border-radius: 24px;
            border: 1px solid var(--panel-border);
            background: linear-gradient(180deg, rgba(15, 28, 51, 0.82), rgba(10, 19, 36, 0.88));
            box-shadow: 0 24px 80px rgba(1, 7, 18, 0.32);
        }

        .card a {
            color: var(--text);
            text-decoration: none;
        }

        .featured-image {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 18px;
            margin-bottom: 18px;
            display: block;
            border: 1px solid var(--panel-border);
        }
    </style>
</head>
<body>
    <main class="shell">
        <header class="hero">
            <div class="eyebrow">Blog Category</div>
            <h1>{{ $category->name }}</h1>
            <p class="subtle">{{ $category->description ?: 'Posts publicados filtrados por categoria editorial.' }}</p>
        </header>

        <section class="grid">
            @forelse ($posts as $post)
                <article class="card">
                    @if ($post->featuredImage?->url())
                        <img class="featured-image" src="{{ $post->featuredImage->url() }}" alt="{{ $post->title }}">
                    @endif
                    <a href="{{ url('/blog/'.$post->slug) }}">
                        <h2>{{ $post->title }}</h2>
                    </a>
                    <p class="subtle">{{ $post->published_at?->format('Y-m-d H:i') ?? $post->updated_at?->format('Y-m-d H:i') }}</p>
                    @if ($showExcerpts)
                        <p>{{ $post->excerpt }}</p>
                    @endif
                </article>
            @empty
                <article class="card">
                    <h2>No published posts in this category.</h2>
                    <p class="subtle">A categoria ainda nao possui posts publicados nesta instalacao.</p>
                </article>
            @endforelse
        </section>
    </main>
</body>
</html>
