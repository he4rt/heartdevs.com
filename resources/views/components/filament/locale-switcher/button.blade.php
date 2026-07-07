@props (['label', 'locale', 'active' => false])

@php
    $ariaLabel = match ($locale) {
        \App\Support\ApplicationLocale::EN => __('app.locale.english'),
        \App\Support\ApplicationLocale::PT_BR => __('app.locale.portuguese'),
        default => $label,
    };
@endphp

<a
    href="{{ route('locale.switch', ['locale' => $locale]) }}"
    aria-label="{{ $ariaLabel }}"
    x-tooltip="{
        content: @js($ariaLabel),
        theme: $store.theme,
    }"
    @class ([
        'fi-locale-switcher-btn flex flex-1 justify-center rounded-md px-3 py-2 text-xs font-semibold tracking-wide outline-hidden transition duration-75',
        'text-primary-500 bg-gray-50 dark:bg-white/5 dark:text-primary-400' => $active,
        'text-gray-400 hover:bg-gray-50 hover:text-gray-500 focus-visible:bg-gray-50 focus-visible:text-gray-500 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-400 dark:focus-visible:bg-white/5' => !$active
    ])
    x-on:click="close()"
>
    {{ $label }}
</a>
