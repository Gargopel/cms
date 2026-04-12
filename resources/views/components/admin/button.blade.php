@props([
    'variant' => 'primary',
    'type' => 'button',
])

@if ($attributes->has('href'))
    <a {{ $attributes->class(['admin-button', "admin-button--{$variant}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['admin-button', "admin-button--{$variant}"]) }}>
        {{ $slot }}
    </button>
@endif
