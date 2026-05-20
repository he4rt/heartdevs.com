<div>
    <section class="space-y-4">
        @forelse ($this->enrollments as $enrollment)
            <a
                href="{{ $this->eventUrl($enrollment) }}"
                wire:key="enrollment-{{ $enrollment->id }}"
                class="hover:border-primary-300 dark:hover:border-primary-500/50 block rounded-xl border border-gray-200 p-5 transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 space-y-1">
                        <h3 class="truncate text-base font-semibold text-gray-950 dark:text-white">
                            {{ $enrollment->event->title }}
                        </h3>

                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                        >
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

                    <x-filament::badge :color="$enrollment->status->getColor()">
                        {{ $enrollment->status->getLabel() }}
                    </x-filament::badge>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-gray-200 p-8 text-center dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('events::pages.no_enrollments') }}</p>
            </div>
        @endforelse
    </section>
</div>
