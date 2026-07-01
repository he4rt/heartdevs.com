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

            @if ($this->enrollment->status->isWaitlisted() && $this->enrollment->waitlist_position)
                <p class="mt-3 text-sm text-amber-700 dark:text-amber-400">
                    {{
                        __('events::pages.waitlist_status', [
                            'position' => $this->enrollment->waitlist_position,
                        ])
                    }}
                </p>
            @endif

            @if ($this->enrollment->status === \He4rt\Events\Enrollment\Enums\EnrollmentStatus::Pending)
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('events::pages.application_pending_hint') }}
                </p>
                @if ($this->enrollment->application_data && $this->event->enrollmentPolicy?->application_schema)
                    <div class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-white/5">
                        <p class="text-xs font-medium tracking-wide text-gray-400 uppercase">
                            {{ __('events::pages.application_your_answers') }}
                        </p>
                        @foreach ($this->event->enrollmentPolicy->application_schema as $index => $field)
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $field['label'] ?? '' }}</p>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    @php $answer = $this->enrollment->application_data[$index] ?? null; @endphp
                                    @if (is_array($answer))
                                        {{ $answer !== [] ? implode(', ', $answer) : '—' }}
                                    @else
                                        {{ $answer ?? '—' }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($this->enrollment->status === \He4rt\Events\Enrollment\Enums\EnrollmentStatus::Rejected)
                <p class="mt-3 text-sm text-red-600 dark:text-red-400">
                    {{ __('events::pages.application_rejected_hint') }}
                </p>
                @if ($this->enrollment->rejection_reason)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{
                            __('events::pages.application_rejection_reason', [
                                'reason' => $this->enrollment->rejection_reason,
                            ])
                        }}
                    </p>
                @endif
            @endif
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
    @elseif ($this->canApply)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ __('events::pages.apply_hint') }}</p>

            <form wire:submit="apply" class="space-y-4">
                @foreach ($this->event->enrollmentPolicy->application_schema ?? [] as $index => $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $field['label'] ?? '' }}
                            @if ($field['required'] ?? false)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if (($field['type'] ?? '') === 'textarea')
                            <textarea
                                wire:model="applicationFormData.{{ $index }}"
                                rows="3"
                                class="focus:border-primary-500 focus:ring-primary-500 mt-1 block w-full rounded-lg border border-gray-300 p-2 text-sm shadow-sm dark:border-white/20 dark:bg-white/5 dark:text-white"
                                @if ($field['required'] ?? false) required @endif
                            ></textarea>
                        @elseif (($field['type'] ?? '') === 'select')
                            <select
                                wire:model="applicationFormData.{{ $index }}"
                                class="focus:border-primary-500 focus:ring-primary-500 mt-1 block w-full rounded-lg border border-gray-300 p-2 text-sm shadow-sm dark:border-white/20 dark:bg-white/5 dark:text-white"
                                @if ($field['required'] ?? false) required @endif
                            >
                                <option value="">Select an option</option>
                                @foreach ($field['options'] ?? [] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif (($field['type'] ?? '') === 'checkbox')
                            <div class="mt-1 space-y-2">
                                @foreach ($field['options'] ?? [] as $option)
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            wire:model="applicationFormData.{{ $index }}"
                                            value="{{ $option }}"
                                            class="text-primary-600 focus:ring-primary-500 rounded border-gray-300"
                                        />
                                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <input
                                type="text"
                                wire:model="applicationFormData.{{ $index }}"
                                class="focus:border-primary-500 focus:ring-primary-500 mt-1 block w-full rounded-lg border border-gray-300 p-2 text-sm shadow-sm dark:border-white/20 dark:bg-white/5 dark:text-white"
                                @if ($field['required'] ?? false) required @endif
                            />
                        @endif
                    </div>
                @endforeach

                <x-filament::button type="submit" wire:loading.attr="disabled">
                    {{ __('events::pages.apply_submit') }}
                </x-filament::button>
            </form>
        </div>
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
    @elseif ($this->isEventFull)
        <div class="rounded-xl border border-gray-200 p-5 dark:border-white/10">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('events::pages.event_full') }}</p>
        </div>
    @endif

    <livewire:numeric-code-check-in
        :event-id="$this->eventId"
        :key="'numeric-code-' . $this->eventId"
        wire:key="'numeric-code-check-in-' . $this->eventId"
    />
</div>
