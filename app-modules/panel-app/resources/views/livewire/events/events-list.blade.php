<div>
    <section class="space-y-4">
        @forelse ($this->events as $event)
            <a
                href="{{ $this->eventUrl($event) }}"
                wire:key="event-{{ $event->id }}"
                class="hover:border-primary-300 dark:hover:border-primary-500/50 block rounded-xl border border-gray-200 p-5 transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white">
                            {{ $event->title }}
                        </h3>

                        @if ($event->description)
                            <p class="line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $event->description }}
                            </p>
                        @endif

                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span>{{ $event->starts_at->format('d/m/Y H:i') }}</span>

                            @if ($event->location)
                                <span>{{ $event->location }}</span>
                            @endif
                        </div>
                    </div>

                    <x-filament::badge :color="$event->event_type->getColor()">
                        {{ $event->event_type->getLabel() }}
                    </x-filament::badge>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-gray-200 p-8 text-center dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('events::pages.no_upcoming_events') }}</p>
            </div>
        @endforelse
    </section>
</div>
