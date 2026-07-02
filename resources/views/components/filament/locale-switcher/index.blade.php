@php
    use App\Support\ApplicationLocale;

    $currentLocale = app()->getLocale();
@endphp

<div class="fi-locale-switcher grid grid-flow-col gap-x-1">
    <x-filament.locale-switcher.button
        :label="__('app.locale.english_short')"
        :locale="ApplicationLocale::EN"
        :active="$currentLocale === ApplicationLocale::EN"
    />

    <x-filament.locale-switcher.button
        :label="__('app.locale.portuguese_short')"
        :locale="ApplicationLocale::PT_BR"
        :active="$currentLocale === ApplicationLocale::PT_BR"
    />
</div>
