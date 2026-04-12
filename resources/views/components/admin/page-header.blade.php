@props([
    'title',
    'subtitle' => null,
    'eyebrow' => 'Core Admin',
])

<div class="page-header">
    <div class="page-header-copy">
        <span class="page-eyebrow">{{ $eyebrow }}</span>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if (trim((string) $slot) !== '')
        <div class="page-header-actions">
            {{ $slot }}
        </div>
    @endif
</div>
