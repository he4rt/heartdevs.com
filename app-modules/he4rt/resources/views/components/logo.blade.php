@props([
    'href' => '/',
    'path' => 'images/logo.svg',
    'size' => 'md',
])

@php
    $sizeCls = match ($size) {
        'sm' => 'max-w-20',
        'md' => 'max-w-40',
    };
@endphp

<a href="{{ $href }}">
    <img
        src="{{ asset($path) }}"
        alt="logo"
        {{
            $attributes->class([
                $sizeCls,
                'mb-4',
                'w-full',
                'cursor-pointer',
            ])
        }}
    />
</a>
