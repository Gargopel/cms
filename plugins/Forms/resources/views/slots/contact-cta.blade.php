<article>
    <p style="margin: 0 0 10px; color: #33d1ff; font-size: 0.78rem; letter-spacing: 0.14em; text-transform: uppercase;">
        Forms Plugin Slot
    </p>
    <h2 style="margin: 0 0 10px; font-size: 1.55rem; line-height: 1.15; letter-spacing: -0.03em;">
        {{ $title }}
    </h2>
    <p style="margin: 0; color: #a8b7d6; line-height: 1.7;">
        {{ $description }}
    </p>

    <div style="margin-top: 14px; color: #edf2ff;">
        <strong>{{ $form->title }}</strong>
        @if (! empty($form->description))
            <p style="margin: 8px 0 0; color: #a8b7d6; line-height: 1.6;">{{ $form->description }}</p>
        @endif
    </div>

    <div style="margin-top: 18px;">
        <a href="{{ $href ?? $fallback_href }}" style="display: inline-flex; padding: 12px 18px; border-radius: 999px; color: #03101f; background: linear-gradient(135deg, #33d1ff 0%, #8c63ff 100%); font-weight: 700; text-decoration: none;">
            {{ $cta_label }}
        </a>
    </div>
</article>
