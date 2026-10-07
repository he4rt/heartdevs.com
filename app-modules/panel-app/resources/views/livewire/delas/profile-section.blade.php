@php
    use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;

    $eligibility = $this->eligibility;
    $state = $eligibility->state;
    $displayTimezone = config('app.display_timezone');
    $switchOn = in_array($state, [DelasEligibilityState::Pending, DelasEligibilityState::Member], true);
@endphp

<div id="he4rt-delas">
    <x-filament::section>
        <x-slot name="heading">
            <span class="inline-flex items-center gap-2">
                <x-panel-app::delas.logo class="text-delas-500 h-4 w-auto" />
                {{ __('panel-app::delas.name') }}
            </span>
        </x-slot>
        <x-slot name="description">{{ __('panel-app::delas.profile.intro') }}</x-slot>

        <div class="space-y-4">
            @if ($state !== DelasEligibilityState::Member)
                <div class="flex items-start gap-3">
                    <button
                        type="button"
                        role="switch"
                        id="delas-request-switch"
                        aria-checked="{{ $switchOn ? 'true' : 'false' }}"
                        @if ($state === DelasEligibilityState::CanRequest) aria-describedby="delas-request-switch-hint" @endif
                        @if ($state === DelasEligibilityState::CanRequest)
                            wire:click="mountAction('request')"
                        @else
                            disabled
                        @endif
                        @class([
                            'relative mt-0.5 inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600',
                            'bg-delas-600' => $switchOn,
                            'bg-gray-200 dark:bg-gray-700' => !$switchOn,
                            'cursor-pointer' => $state === DelasEligibilityState::CanRequest,
                            'cursor-not-allowed opacity-60' => $state !== DelasEligibilityState::CanRequest,
                        ])
                    >
                        <span
                            @class([
                                'pointer-events-none inline-block size-5 rounded-full bg-white shadow ring-0 transition',
                                'translate-x-5' => $switchOn,
                                'translate-x-0' => !$switchOn,
                            ])
                        ></span>
                    </button>
                    <div>
                        <label for="delas-request-switch" class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('panel-app::delas.profile.toggle') }}
                        </label>
                        @if ($state === DelasEligibilityState::CanRequest)
                            <p id="delas-request-switch-hint" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('panel-app::delas.profile.toggle_hint') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            @switch($state)
                @case(DelasEligibilityState::Pending)
                    <x-panel-app::delas.callout tone="warning" icon="heroicon-o-clock" role="status">
                        <x-slot name="title">{{ __('panel-app::delas.profile.pending_title') }}</x-slot>
                        {{
                            __('panel-app::delas.profile.pending_body', [
                                'date' => $eligibility->request?->requested_at->timezone($displayTimezone)->format('d/m/Y H:i'),
                            ])
                        }}
                    </x-panel-app::delas.callout>

                    @break
                @case(DelasEligibilityState::Member)
                    <x-panel-app::delas.callout tone="success" icon="heroicon-o-check-circle" role="status">
                        <x-slot name="title">{{ __('panel-app::delas.profile.member_title') }}</x-slot>
                        {{ __('panel-app::delas.profile.member_body') }}
                        <div class="mt-3"><x-panel-app::delas.tag /></div>
                    </x-panel-app::delas.callout>

                    @break
                @case(DelasEligibilityState::Cooldown)
                    <x-panel-app::delas.callout tone="gray" icon="heroicon-o-clock">
                        <x-slot name="title">{{ __('panel-app::delas.profile.cooldown_title') }}</x-slot>
                        {{
                            __('panel-app::delas.profile.cooldown_body', [
                                'date' => $eligibility->nextAllowedAt?->timezone($displayTimezone)->format('d/m'),
                            ])
                        }}
                    </x-panel-app::delas.callout>

                    @break
                @case(DelasEligibilityState::Blocked)
                    <x-panel-app::delas.callout tone="gray" icon="heroicon-o-information-circle">
                        <x-slot name="title">{{ __('panel-app::delas.profile.blocked_title') }}</x-slot>
                    </x-panel-app::delas.callout>

                    @break
            @endswitch
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</div>
