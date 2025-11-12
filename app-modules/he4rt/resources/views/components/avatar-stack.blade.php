@props([
    'images' => [],
    'limit' => 5,
    'size' => 'lg',
    'circular' => true,
])

@php
    $visibleImages = array_slice($images, 0, $limit);

    $plusClasses = collect([
        $circular ? 'hp-circular' : null,
        match ($size) {
            'sm', 'md', 'lg' => "hp-size-{$size}",
            default => $size,
        },
    ])
        ->filter()
        ->implode(' ');
@endphp

<div {{ $attributes->class('hp-avatar-stack') }}>
    <div class="hp-avatar-stack-images">
        @foreach ($visibleImages as $index => $image)
            <x-he4rt::avatar
                :src="$image"
                :size="$size"
                :circular="$circular"
                alt="Avatar {{ $index + 1 }}"
                style="z-index: {{ 10 + $index }};"
            />
        @endforeach
    </div>

    @if ($slot->isNotEmpty())
        <div class="hp-avatar-stack-label">
            {{ $slot }}
        </div>
    @endif
</div>
