<div class="flex items-start justify-between gap-4">
    <div class="min-w-0 space-y-1">
        <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white">{{ $enrollment->event->title }}</h3>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $enrollment->event->starts_at->format('d/m/Y H:i') }}</span>

            @if ($enrollment->enrolled_at)
                <span>{{
                    __('events::pages.enrolled_at', [
                        'date' => $enrollment->enrolled_at->format('d/m/Y H:i'),
                    ])
                }}</span>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 flex-col items-end gap-2">
        <x-filament::badge :color="$enrollment->status->getColor()">
            {{ $enrollment->status->getLabel() }}
        </x-filament::badge>

        <x-filament::badge :color="$enrollment->event->status->getColor()">
            {{ $enrollment->event->status->getLabel() }}
        </x-filament::badge>
    </div>
</div>
