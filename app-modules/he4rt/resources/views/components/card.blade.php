@props([
    'as' => 'div',
    'href' => null,
    'interactive' => true,
    'disabled' => false,
    'density' => 'normal', // normal | compact
    'target' => null,
    'rel' => null,
    'variant' => 'solid',
])

@php
    $isInteractive = $interactive && ! $disabled;
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

    $classes = collect([
        'hp-card',
        'hp-card-density-' . $density,
        'hp-card-variant-' . $variant,
        $isInteractive ? 'hp-card-interactive' : null,
        $disabled ? 'hp-card-disabled' : null,
    ])
        ->filter()
        ->implode(' ');
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes])->merge($linkAttrs) }}>
    {{-- Slot do Ícone --}}
    @isset($icon)
        <div {{ $icon->attributes->class('hp-card-icon') }}>
            {{ $icon }}
        </div>
    @endisset

    {{-- Slot do Header --}}
    @isset($header)
        <div {{ $header->attributes->class('hp-card-header') }}>
            {{ $header }}
        </div>
    @endisset

    {{-- Corpo do Card (Título e Descrição) --}}
    @if (isset($title) || isset($description))
        <div class="hp-card-body">
            @isset($title)
                <h3 {{ $title->attributes->class('hp-card-title') }}>
                    {{ $title }}
                </h3>
            @endisset

            @isset($description)
                <p {{ $description->attributes->class('hp-card-description') }}>
                    {{ $description }}
                </p>
            @endisset
        </div>
    @endif

    {{ $slot }}

    {{-- Slot de Tags --}}
    @isset($tags)
        <div {{ $tags->attributes->class('hp-card-tags') }}>
            {{ $tags }}
        </div>
    @endisset

    {{-- Slot de Ações --}}
    @isset($actions)
        <div {{ $actions->attributes->class('hp-card-actions') }}>
            {{ $actions }}
        </div>
    @endisset

    {{-- Slot do Rodapé --}}
    @isset($footer)
        <div {{ $footer->attributes->class('hp-card-footer') }}>
            {{ $footer }}
        </div>
    @endisset
</{{ $tag }}>
