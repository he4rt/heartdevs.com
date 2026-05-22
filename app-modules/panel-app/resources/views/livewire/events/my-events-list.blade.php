<div>
    <section class="space-y-4">
        @forelse ($this->enrollments as $enrollment)
            @if ($this->canOpenEvent($enrollment))
                <a
                    href="{{ $this->eventUrl($enrollment) }}"
                    wire:key="enrollment-{{ $enrollment->id }}"
                    class="hover:border-primary-300 dark:hover:border-primary-500/50 block rounded-xl border border-gray-200 p-5 transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                >
                    @include ('panel-app::livewire.events.partials.my-events-list-item', ['enrollment' => $enrollment])
                </a>
            @else
                <div
                    wire:key="enrollment-{{ $enrollment->id }}"
                    class="block rounded-xl border border-gray-200 p-5 dark:border-white/10"
                >
                    @include ('panel-app::livewire.events.partials.my-events-list-item', ['enrollment' => $enrollment])
                </div>
            @endif
        @empty
            <div class="rounded-xl border border-gray-200 p-8 text-center dark:border-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('events::pages.no_enrollments') }}</p>
            </div>
        @endforelse
    </section>
</div>
