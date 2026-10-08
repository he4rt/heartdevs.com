@php
    /** @var \He4rt\Identity\User\Models\User $person */
    $profile = $person->profile;
    $discord = $person->providers
        ->where('provider', \He4rt\Identity\ExternalIdentity\Enums\IdentityProvider::Discord)
        ->filter->isConnected()
        ->sortByDesc('connected_at')
        ->first();
    $discordName = $discord?->metadata['username'] ?? $discord?->external_account_id;
    $hasProfile = $profile && ($profile->headline || $profile->seniority_level || $profile->years_experience || $profile->about);
@endphp

{{-- Resumo só leitura para a moderação decidir: sem e-mail, data de nascimento nem localização. --}}
<div class="flex flex-col gap-5 text-sm">
    <div class="flex items-center gap-3">
        <x-filament::avatar :src="$person->getFilamentAvatarUrl()" :alt="$person->name" size="lg" />
        <div>
            <p class="font-semibold text-gray-950 dark:text-white">{{ $person->name }}</p>
            <p class="text-gray-500 dark:text-gray-400">{{ '@'.$person->username }}</p>
        </div>
    </div>

    <dl class="flex flex-col gap-3">
        @if ($profile?->headline)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.headline') }}</dt>
                <dd class="text-gray-950 dark:text-white">{{ $profile->headline }}</dd>
            </div>
        @endif

        @if ($profile?->seniority_level || $profile?->years_experience)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.seniority') }}</dt>
                <dd class="text-gray-950 dark:text-white">
                    {{ collect([$profile->seniority_level?->getLabel(), $profile->years_experience !== null ? trans_choice('panel-admin::delas.profile.years', $profile->years_experience, ['years' => $profile->years_experience]) : null])->filter()->implode(' · ') }}
                </dd>
            </div>
        @endif

        @if ($profile?->about)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.about') }}</dt>
                <dd class="whitespace-pre-line text-gray-950 dark:text-white">{{ $profile->about }}</dd>
            </div>
        @endif

        @unless ($hasProfile)
            <p class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.empty') }}</p>
        @endunless

        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.discord') }}</dt>
            <dd class="text-gray-950 dark:text-white">
                @if ($discord && $discord->external_account_id)
                    <a
                        href="https://discord.com/users/{{ $discord->external_account_id }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary-600 underline dark:text-primary-400"
                    >
                        {{ $discordName }}
                    </a>
                @else
                    {{ __('panel-admin::delas.profile.discord_none') }}
                @endif
            </dd>
        </div>

        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.since') }}</dt>
            <dd class="text-gray-950 dark:text-white">
                {{ $person->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y') }}
            </dd>
        </div>
    </dl>

    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('panel-admin::delas.profile.note') }}</p>
</div>
