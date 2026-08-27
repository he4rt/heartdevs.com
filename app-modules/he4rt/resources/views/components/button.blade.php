@props([
    "as" => "button",
    "href" => null,
    "type" => "button",
    "variant" => "solid", // solid | outline
    "size" => "md", // xs | sm | md | lg
    "rounded" => "md", // sm | md | lg | full
    "block" => false,
    "disabled" => false,
    "loading" => false,
    "iconOnly" => false,
    "icon" => null,
    "iconPosition" => "trailing",
])

@aware(["interactive" => false])

@php
    $isLink = filled($href);
    $tag = $isLink ? "a" : $as;
    $isBusy = (bool) $loading;
    $isDisabled = (bool) $disabled || $isBusy;

    $hasLeading = isset($leading) || (filled($icon) && $iconPosition === "leading");
    $hasTrailing = isset($trailing) || (filled($icon) && $iconPosition === "trailing");

    $classes = collect([
        "hp-button",
        "hp-button-" . $variant,
        "hp-button-size-" . $size,
        "hp-button-rounded-" . $rounded,
        $block ? "hp-button-block" : null,
        $iconOnly ? "hp-button-icon-only" : null,
    ])
        ->filter()
        ->implode(" ");
@endphp

<{{ $tag }}
    @if (! $isLink)
        type="{{ $type }}"
        @if ($isDisabled)
            disabled
        @endif
    @else
        href="{{ $href }}"
        @if ($isDisabled)
            aria-disabled="true"
            tabindex="-1"
        @endif
    @endif
    @if ($isBusy) aria-busy="true" @endif
    {{ $attributes->class($classes) }}
>
    @if ($hasLeading)
        <span class="hp-button-icon">
            @if ($icon)
                <x-dynamic-component :component="$icon" class="h-full w-full" />
            @else
                {{ $leading }}
            @endif
        </span>
    @endif

    @unless ($iconOnly)
        <span class="hp-button-label {{ $isBusy ? "opacity-0" : "opacity-100" }}">
            {{ $slot }}
        </span>
    @endunless

    @if ($hasTrailing)
        <span class="hp-button-icon">
            @if ($icon)
                <x-dynamic-component :component="$icon" class="h-full w-full" />
            @else
                {{ $trailing }}
            @endif
        </span>
    @endif

    @if ($isBusy)
        <span class="hp-button-spinner">
            <svg class="animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path
                    class="opacity-75"
                    d="M4 12a8 8 0 018-8"
                    stroke="currentColor"
                    stroke-width="4"
                    stroke-linecap="round"
                />
            </svg>
        </span>
    @endif
</{{ $tag }}>
