@php
    use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

    /**
     * @var \He4rt\Identity\User\Models\User $person
     * @var \He4rt\Delas\TagRequest\ValueObjects\DelasEligibilityResult $eligibility
     */
    $profile = $person->profile;
    $discord = $person->providers
        ->where('provider', IdentityProvider::Discord)
        ->filter->isConnected()
        ->sortByDesc('connected_at')
        ->first();
    $discordName = $discord?->metadata['username'] ?? $discord?->external_account_id;
    $initials = collect(explode(' ', $person->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $avatarUrl = $person->getFilamentAvatarUrl();
    $seniority = collect([
        $profile?->seniority_level?->getLabel(),
        $profile?->years_experience !== null
            ? trans_choice('panel-admin::delas.profile.years', $profile->years_experience, ['years' => $profile->years_experience])
            : null,
    ])->filter()->implode(' · ');
    $hasProfile = $profile?->headline || $seniority !== '' || $profile?->about;
    $state = $eligibility->state;
@endphp

{{-- Resumo só leitura para a moderação decidir: sem e-mail, data de nascimento nem localização. --}}
<div class="flex flex-col gap-6 text-sm">
    {{-- Cartão: faixa do Hub, foto (ou iniciais), nome e a situação na He4rt Delas. --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
        <div class="h-16 bg-gradient-to-r from-purple-600 to-purple-500"></div>

        <div class="flex flex-col gap-3 px-4 pb-4">
            <div
                class="relative -mt-8 flex size-16 items-center justify-center overflow-hidden rounded-full bg-purple-100 text-lg font-semibold text-purple-700 ring-4 ring-white dark:bg-purple-500/20 dark:text-purple-200 dark:ring-gray-900"
            >
                <span>{{ $initials }}</span>
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="" class="absolute inset-0 size-full object-cover" onerror="this.remove()" />
                @endif
            </div>

            <div>
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ $person->name }}</p>
                <p class="text-gray-500 dark:text-gray-400">{{ '@'.$person->username }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge :color="$state->getColor()" :icon="$state->getIcon()">
                    {{ $state->getLabel() }}
                </x-filament::badge>

                @if ($state === \He4rt\Delas\TagRequest\Enums\DelasEligibilityState::Member)
                    <x-he4rt::delas.tag />
                @endif
            </div>
        </div>
    </div>

    {{-- Perfil profissional --}}
    @if ($hasProfile)
        <dl class="flex flex-col gap-4">
            @if ($profile?->headline)
                <div class="flex gap-3">
                    <x-filament::icon icon="heroicon-o-briefcase" class="mt-0.5 size-5 shrink-0 text-gray-400" />
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.headline') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $profile->headline }}</dd>
                    </div>
                </div>
            @endif

            @if ($seniority !== '')
                <div class="flex gap-3">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="mt-0.5 size-5 shrink-0 text-gray-400" />
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.seniority') }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $seniority }}</dd>
                    </div>
                </div>
            @endif

            @if ($profile?->about)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <dt class="mb-1 text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.about') }}</dt>
                    <dd class="whitespace-pre-line text-gray-950 dark:text-white">{{ $profile->about }}</dd>
                </div>
            @endif
        </dl>
    @else
        <div class="flex flex-col items-center gap-2 rounded-lg border border-dashed border-gray-300 p-5 text-center dark:border-white/15">
            <x-filament::icon icon="heroicon-o-user-circle" class="size-8 text-gray-400" />
            <p class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.empty') }}</p>
        </div>
    @endif

    {{-- Comunidade --}}
    <dl class="flex flex-col gap-4 border-t border-gray-200 pt-5 dark:border-white/10">
        <div class="flex gap-3">
            <x-filament::icon icon="fab-discord" class="mt-0.5 size-5 shrink-0 text-[#5865F2]" />
            <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.discord') }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">
                    @if ($discord && $discord->external_account_id)
                        <a
                            href="https://discord.com/users/{{ $discord->external_account_id }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $discordName }}
                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="size-4" />
                        </a>
                    @else
                        <span class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.discord_none') }}</span>
                    @endif
                </dd>
            </div>
        </div>

        <div class="flex gap-3">
            <x-filament::icon icon="heroicon-o-calendar" class="mt-0.5 size-5 shrink-0 text-gray-400" />
            <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.since') }}</dt>
                <dd class="font-medium text-gray-950 dark:text-white">
                    {{ $person->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y') }}
                </dd>
            </div>
        </div>
    </dl>

    <p class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <x-filament::icon icon="heroicon-o-lock-closed" class="size-4 shrink-0" />
        {{ __('panel-admin::delas.profile.note') }}
    </p>
</div>
