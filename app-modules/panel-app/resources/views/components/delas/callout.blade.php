@props(['tone' => 'gray', 'icon' => 'heroicon-o-information-circle'])

@php
    $classes = match ($tone) {
        'warning' => ['border-amber-300/60 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-400/5', 'text-amber-600 dark:text-amber-400', 'text-amber-900 dark:text-amber-200', 'text-amber-800 dark:text-amber-200/80'],
        'success' => ['border-green-300/60 bg-green-50 dark:border-green-400/20 dark:bg-green-400/5', 'text-green-600 dark:text-green-400', 'text-green-900 dark:text-green-200', 'text-green-800 dark:text-green-200/80'],
        'info' => ['border-sky-300/60 bg-sky-50 dark:border-sky-400/20 dark:bg-sky-400/5', 'text-sky-600 dark:text-sky-400', 'text-sky-900 dark:text-sky-200', 'text-sky-800 dark:text-sky-200/80'],
        default => ['border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5', 'text-gray-500 dark:text-gray-400', 'text-gray-900 dark:text-gray-100', 'text-gray-600 dark:text-gray-400'],
    };
@endphp

<div {{ $attributes->class(['flex items-start gap-3 rounded-lg border p-4', $classes[0]]) }}>
    <x-filament::icon :icon="$icon" @class(['mt-0.5 size-5 shrink-0', $classes[1]]) />
    <div class="min-w-0 flex-1">
        @isset($title)
            <p @class(['text-sm font-semibold', $classes[2]])>{{ $title }}</p>
        @endisset

        @if (trim($slot) !== '')
            <div @class(['mt-1 text-sm', $classes[3]])>{{ $slot }}</div>
        @endif
    </div>
</div>
