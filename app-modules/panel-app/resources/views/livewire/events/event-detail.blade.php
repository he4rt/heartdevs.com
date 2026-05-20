<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 p-6 dark:border-white/10">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $this->event->title }}</h2>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span>{{ $this->event->starts_at->format('d/m/Y H:i') }}</span>
                    <span>—</span>
                    <span>{{ $this->event->ends_at->format('d/m/Y H:i') }}</span>

                    @if ($this->event->location)
                        <span>{{ $this->event->location }}</span>
                    @endif
                </div>
            </div>

            <x-filament::badge :color="$this->event->event_type->getColor()">
                {{ $this->event->event_type->getLabel() }}
            </x-filament::badge>
        </div>

        @if ($this->event->description)
            <p class="mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $this->event->description }}</p>
        @endif
    </div>

    @if ($this->enrollment)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('events::pages.enrollment_status_label') }}
                </p>

                <x-filament::badge :color="$this->enrollment->status->getColor()">
                    {{ $this->enrollment->status->getLabel() }}
                </x-filament::badge>
            </div>
        </div>
    @elseif ($this->canConfirmPresence)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ __('events::pages.confirm_presence_hint') }}</p>

            <x-filament::button wire:click="confirmPresence" wire:loading.attr="disabled">
                {{ __('events::pages.confirm_presence') }}
            </x-filament::button>
        </div>
    @endif
</div>
