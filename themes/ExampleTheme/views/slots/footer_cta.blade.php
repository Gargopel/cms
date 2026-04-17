<div class="theme-slot theme-slot-footer-cta" data-slot="{{ $slot }}">
    <div style="margin-bottom: 14px;">
        <span class="eyebrow" style="margin-bottom: 0;">Theme Slot</span>
    </div>

    @foreach ($blocks as $slotBlock)
        <div data-plugin="{{ $slotBlock['plugin_slug'] }}" style="padding: 18px 20px; border-radius: 22px; border: 1px solid rgba(148, 170, 226, 0.12); background: rgba(255, 255, 255, 0.03);">
            {!! $slotBlock['html'] !!}
        </div>
    @endforeach
</div>
