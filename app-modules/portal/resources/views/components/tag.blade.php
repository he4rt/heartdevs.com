@props([
    'as' => 'div',
    'href' => null,
    'disabled' => false,
])

@php
    $tag = $href ? 'a' : $as;

    $linkAttrs = [];
    if ($href) {
        $linkAttrs['href'] = $href;
        if ($target === '_blank' && is_null($rel)) {
            $linkAttrs['rel'] = 'noopener noreferrer';
        }
        if ($target) {
            $linkAttrs['target'] = $target;
        }
        if ($rel) {
            $linkAttrs['rel'] = $rel;
        }
    }

    if ($disabled) {
        $linkAttrs['aria-disabled'] = 'true';
        $linkAttrs['tabindex'] = '-1';
    }
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => 'hp-tag'])->merge($linkAttrs) }}>
    @isset($icon)
        <div {{ $icon->attributes->class('hp-tag-icon') }}>
            {{ $icon }}
        </div>
    @endisset

    <span class="hp-tag-text">
        {{ $slot }}
    </span>
</{{ $tag }}>
