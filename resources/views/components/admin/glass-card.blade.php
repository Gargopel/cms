@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
])

<section {{ $attributes->class(['glass-card', 'glass-card--padded' => $padded]) }}>
    @if ($title || $subtitle)
        <header class="glass-card-header">
            @if ($title)
                <h2>{{ $title }}</h2>
            @endif

            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
