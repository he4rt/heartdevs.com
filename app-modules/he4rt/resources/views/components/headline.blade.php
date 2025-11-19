@props([
    'as' => 'div',
    'align' => 'left',
    'size' => 'lg',
    'animate' => true,
    'keywords' => [],
    'contentLayout' => null,
])

@php
    $tag = $as;

    $classes = collect([
        'hp-headline',
        'hp-headline-align-' . $align,
        'hp-headline-size-' . $size,
        $animate ? 'hp-headline-animated' : null,
    ])
        ->filter()
        ->implode(' ');
@endphp

<div {{ $attributes->class($classes) }}>
    <{{ $tag }} class="hp-headline-container">
        @isset($badge)
            <div class="hp-headline-badge">
                {{ $badge }}
            </div>
        @endisset

        <div class="hp-headline-content">
            @isset($title)
                @php
                    $words = str($title)->explode(' ');
                @endphp

                <h1 class="hp-headline-title">
                    @foreach ($words as $word)
                        @if (in_array(trim($word), $keywords))
                            <span class="hp-headline-highlight">{{ $word }}</span>
                        @else
                            {{ $word }}
                        @endif
                        @unless ($loop->last)
                            {{ ' ' }}
                        @endunless
                    @endforeach
                </h1>
            @endisset

            @isset($subtitle)
                <div {{ $subtitle->attributes->class('hp-headline-subtitle') }}>
                    {{ $subtitle }}
                </div>
            @endisset

            @isset($description)
                <p class="hp-headline-description">
                    {{ $description }}
                </p>
            @endisset
        </div>

        @isset($actions)
            <div class="hp-headline-actions">
                {{ $actions }}
            </div>
        @endisset
    </{{ $tag }}>
</div>
