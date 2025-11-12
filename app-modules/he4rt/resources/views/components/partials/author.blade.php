@props([
    'as' => 'div',
    'href' => null,
    'src' => '',
    'name' => '',
    'title' => null,
    'size' => 'md', // sm | md | lg
    'target' => null,
    'rel' => null,
])

@php
    $isLink = filled($href);
    $tag = $isLink ? 'a' : $as;

    $classes = collect(['hp-author', 'hp-author-size-' . $size])
        ->filter()
        ->implode(' ');

    $linkAttrs = [];
    if ($isLink) {
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
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes])->merge($linkAttrs) }}>
    <div>
        <x-he4rt::avatar :src="$src" alt="{{ $name }}" :size="$size" :circular="true" />
    </div>

    <div class="hp-author-content">
        <span class="hp-author-name">{{ $name }}</span>

        @if ($title)
            <span class="hp-author-title">{{ $title }}</span>
        @endif
    </div>
</{{ $tag }}>
