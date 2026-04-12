@props([
    'value',
])

@php
    $normalized = strtolower((string) $value);
    $tone = match ($normalized) {
        'valid', 'enabled', 'ready', 'ok' => 'success',
        'invalid', 'failed', 'error' => 'danger',
        'incompatible', 'disabled', 'warning' => 'warning',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->class(['status-badge', "status-badge--{$tone}"]) }}>
    {{ $value }}
</span>
