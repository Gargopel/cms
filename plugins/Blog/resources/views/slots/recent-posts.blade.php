<article>
    <p style="margin: 0 0 10px; color: #33d1ff; font-size: 0.78rem; letter-spacing: 0.14em; text-transform: uppercase;">
        {{ $title }}
    </p>
    <p style="margin: 0 0 18px; color: #a8b7d6; line-height: 1.7;">
        {{ $description }}
    </p>

    <div style="display: grid; gap: 12px;">
        @foreach ($posts as $post)
            <a href="{{ url('/blog/'.$post->slug) }}" style="display: block; padding: 14px 16px; border-radius: 18px; border: 1px solid rgba(148, 170, 226, 0.12); background: rgba(255, 255, 255, 0.03); text-decoration: none;">
                <strong style="display: block; margin-bottom: 6px; color: #edf2ff; line-height: 1.35;">
                    {{ $post->title }}
                </strong>
                <span style="display: block; color: #a8b7d6; font-size: 0.88rem; line-height: 1.55;">
                    {{ $post->category?->name ?? 'Published post' }}
                    •
                    {{ $post->published_at?->format('Y-m-d') ?? $post->updated_at?->format('Y-m-d') }}
                </span>
            </a>
        @endforeach
    </div>

    <div style="margin-top: 18px;">
        <a href="{{ $browse_href }}" style="display: inline-flex; padding: 12px 18px; border-radius: 999px; color: #03101f; background: linear-gradient(135deg, #33d1ff 0%, #8c63ff 100%); font-weight: 700; text-decoration: none;">
            Browse all posts
        </a>
    </div>
</article>
