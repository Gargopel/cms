<div class="theme-slot theme-slot-{{ str_replace('_', '-', $slot) }}" data-slot="{{ $slot }}">
    @foreach ($blocks as $slotBlock)
        <div class="theme-slot-block" data-plugin="{{ $slotBlock['plugin_slug'] }}" data-block-key="{{ $slotBlock['key'] }}">
            {!! $slotBlock['html'] !!}
        </div>
    @endforeach
</div>
