@props([
    'type' => 'fade-up-glass',
    'delay' => null,
    'duration' => '500',
    'observe' => false,
])

@php
    $definitions = [
        'fade-up-glass' => [
            'start' => 'translate-y-8 opacity-100',
            'end' => 'translate-y-0 opacity-100',
        ],
        'fade-up' => [
            'start' => 'translate-y-8 opacity-0',
            'end' => 'translate-y-0 opacity-100',
        ],
        'fade-down' => [
            'start' => '-translate-y-8 opacity-0',
            'end' => 'translate-y-0 opacity-100',
        ],
        'fade-right' => [
            'start' => '-translate-x-8 opacity-0',
            'end' => 'translate-x-0 opacity-100',
        ],
        'fade-left' => [
            'start' => 'translate-x-8 opacity-0',
            'end' => 'translate-x-0 opacity-100',
        ],
        'scale' => [
            'start' => 'scale-90 opacity-0',
            'end' => 'scale-100 opacity-100',
        ],
        'blur' => [
            'start' => 'opacity-0 blur-sm',
            'end' => 'blur-0 opacity-100',
        ],
    ];

    $anim = $definitions[$type];

    $enterClass = 'transition-all ease-in';
    $enterClass .= ' duration-' . $duration;

    if ($delay) {
        $enterClass .= ' delay-' . $delay;
    }
@endphp

@if ($observe)
    <div x-data="{ visible: false }" x-intersect.once.threshold.20="visible = true" {{ $attributes }}>
        <div
            x-show="visible"
            x-transition:enter="{{ $enterClass }}"
            x-transition:enter-start="{{ $anim['start'] }}"
            x-transition:enter-end="{{ $anim['end'] }}"
            x-cloak
        >
            {{ $slot }}
        </div>
    </div>
@else
    <div
        x-show="visible"
        x-transition:enter="{{ $enterClass }}"
        x-transition:enter-start="{{ $anim['start'] }}"
        x-transition:enter-end="{{ $anim['end'] }}"
        x-cloak
        {{ $attributes }}
    >
        {{ $slot }}
    </div>
@endif
