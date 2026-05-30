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
        @if ($this->qrToken)
            <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ __('events::pages.my_qr_code') }}
                    </h3>

                    @if ($this->hasCheckedInToday)
                        <x-filament::badge color="success">
                            {{ __('events::pages.checked_in_today') }}
                        </x-filament::badge>
                    @endif
                </div>

                <div class="flex justify-center" id="qr-svg-wrapper">{!! $this->qrCodeSvg !!}</div>

                <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
                    {{ __('events::pages.qr_code_hint') }}
                </p>

                <div
                    class="mt-4 flex flex-wrap justify-center gap-3"
                    x-data="{ copied: false, token: '{{ $this->qrToken->token }}' }"
                >
                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="
                            navigator.clipboard.writeText(token).then(() => {
                                copied = true;
                                setTimeout(() => (copied = false), 2000);
                            })
                        "
                    >
                        <span x-show="!copied">{{ __('events::pages.copy_token') }}</span>
                        <span x-show="copied" x-cloak>{{ __('events::pages.token_copied') }}</span>
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        size="sm"
                        x-on:click="
                            const svg = document.querySelector('#qr-svg-wrapper svg').outerHTML;
                            const blob = new Blob([svg], { type: 'image/svg+xml' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'qrcode-{{ $this->event->slug }}.svg';
                            a.click();
                            URL.revokeObjectURL(url);
                        "
                    >
                        {{ __('events::pages.download_qr') }}
                    </x-filament::button>
                </div>
            </div>
        @endif
        @if ($this->checkIns->isNotEmpty())
            <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
                <h3 class="mb-3 text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('events::pages.check_in_history') }}
                </h3>

                <ul class="space-y-2">
                    @foreach ($this->checkIns as $checkIn)
                        <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <x-filament::icon
                                icon="heroicon-m-check-circle"
                                class="text-success-500 h-4 w-4 shrink-0"
                            />
                            {{ $checkIn->event_date->format('d/m/Y') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @elseif ($this->canConfirmPresence)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10" x-data="{ confirming: false }">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ __('events::pages.confirm_presence_hint') }}</p>

            <div x-show="!confirming">
                <x-filament::button x-on:click="confirming = true">
                    {{ __('events::pages.confirm_presence') }}
                </x-filament::button>
            </div>

            <div x-show="confirming" x-cloak class="space-y-3">
                <p class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ __('events::pages.confirm_presence_prompt') }}
                </p>

                <div class="flex gap-3">
                    <x-filament::button wire:click="confirmPresence" wire:loading.attr="disabled">
                        {{ __('events::pages.confirm_presence_yes') }}
                    </x-filament::button>

                    <x-filament::button color="gray" x-on:click="confirming = false">
                        {{ __('events::pages.confirm_presence_cancel') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
