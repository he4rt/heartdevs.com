@props([
    'circular' => true,
    'size' => 'md',
    'src' => null,
    'alt' => '',
])

@php
    $classes = $attributes->class([
        'hp-avatar',
        'hp-circular' => $circular,
        'hp-size-' . $size,
    ]);
@endphp

@if ($src)
    <img {{ $classes }} src="{{ $src }}" alt="{{ $alt }}" />
@else
    <div
        {{ $classes->merge(['class' => 'flex items-center justify-center overflow-hidden bg-transparent text-text-medium']) }}
    >
        <x-heroicon-o-user-circle class="h-full w-full" />
    </div>
@endif
