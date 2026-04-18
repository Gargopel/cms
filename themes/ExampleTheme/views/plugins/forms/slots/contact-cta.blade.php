<article style="display: grid; gap: 14px;">
    <div>
        <p style="margin: 0 0 8px; color: #33d1ff; font-size: 0.78rem; letter-spacing: 0.16em; text-transform: uppercase;">
            Theme Override
        </p>
        <h2 style="margin: 0 0 10px; font-size: 1.5rem; line-height: 1.1;">{{ $title }}</h2>
        <p style="margin: 0; color: #a8b7d6; line-height: 1.7;">{{ $description }}</p>
    </div>

    <div style="padding: 16px 18px; border-radius: 20px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(148, 170, 226, 0.12);">
        <strong style="display: block; color: #edf2ff;">{{ $form->title }}</strong>
        @if (! empty($form->description))
            <p style="margin: 8px 0 0; color: #a8b7d6; line-height: 1.6;">{{ $form->description }}</p>
        @endif
    </div>

    <a href="{{ $href ?? $fallback_href }}" style="display: inline-flex; width: fit-content; padding: 12px 18px; border-radius: 999px; color: #03101f; background: linear-gradient(135deg, #33d1ff 0%, #8c63ff 100%); font-weight: 700; text-decoration: none;">
        {{ $cta_label }}
    </a>
</article>
