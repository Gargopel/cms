<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (isset($seo) && $seo && \Illuminate\Support\Facades\View::exists('seo::partials.meta'))
        @include('seo::partials.meta', ['seo' => $seo])
    @else
        <title>Blog - {{ config('app.name') }}</title>
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

        .category-pill {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            color: var(--accent);
            background: rgba(51, 209, 255, 0.08);
            border: 1px solid rgba(51, 209, 255, 0.18);
            margin-bottom: 12px;
            font-size: 0.82rem;
            text-decoration: none;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 12px 0 0;
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

        .grid {
            display: grid;
            gap: 18px;
        }

        .search-form {
            display: grid;
            gap: 12px;
            margin: 0 0 28px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid var(--panel-border);
            background: rgba(15, 28, 51, 0.62);
        }

        .search-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-row input {
            flex: 1 1 280px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(158, 176, 206, 0.16);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
        }

        .search-row button,
        .search-row a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 999px;
            border: 0;
            text-decoration: none;
            font-weight: 600;
        }

        .search-row button {
            color: #03101f;
            cursor: pointer;
            background: linear-gradient(135deg, #33d1ff 0%, #8c63ff 100%);
        }

        .search-row a {
            color: var(--text);
            border: 1px solid rgba(158, 176, 206, 0.18);
            background: rgba(255, 255, 255, 0.04);
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
            <div class="eyebrow">Blog Plugin</div>
            <h1>{{ $blogTitle }}</h1>
            <p class="subtle">{{ $blogIntro }}</p>
        </header>

        <form method="GET" action="{{ url('/blog') }}" class="search-form">
            <div class="search-row">
                <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search published posts by title, slug or excerpt">
                <button type="submit">Search</button>
                @if (! empty($search))
                    <a href="{{ url('/blog') }}">Reset</a>
                @endif
            </div>
            @if (! empty($search))
                <p class="subtle">Showing public results for "{{ $search }}". Draft posts never appear here.</p>
            @endif
        </form>

        <section class="grid">
            @forelse ($posts as $post)
                <article class="card">
                    @if ($post->featuredImage?->url())
                        <img class="featured-image" src="{{ $post->featuredImage->url() }}" alt="{{ $post->title }}">
                    @endif
                    @if ($post->category)
                        <a href="{{ url('/blog/category/'.$post->category->slug) }}" class="category-pill">{{ $post->category->name }}</a>
                    @endif
                    <a href="{{ url('/blog/'.$post->slug) }}">
                        <h2>{{ $post->title }}</h2>
                    </a>
                    <p class="subtle">{{ $post->published_at?->format('Y-m-d H:i') ?? $post->updated_at?->format('Y-m-d H:i') }}</p>
                    @if ($showExcerpts)
                        <p>{{ $post->excerpt }}</p>
                    @endif
                    @if ($post->tags->isNotEmpty())
                        <div class="tag-row">
                            @foreach ($post->tags as $tag)
                                <a href="{{ url('/blog/tag/'.$tag->slug) }}" class="tag-pill">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <article class="card">
                    <h2>No published posts yet.</h2>
                    <p class="subtle">O plugin oficial Blog ainda nao possui posts publicados nesta instalacao.</p>
                </article>
            @endforelse
        </section>
    </main>
</body>
</html>
