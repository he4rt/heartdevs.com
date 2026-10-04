@php
    use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
    use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

    $providers = array_values(array_filter(
        IdentityProvider::supportedProviders(),
        fn (IdentityProvider $provider): bool => $provider->isEnabled()
            && $provider->getCredentialsType() === CredentialsType::OAuth2,
    ));

    $brand = [
        'discord' => 'bg-[#5865F2] hover:bg-[#4752C4] text-white',
        'github' => 'bg-zinc-800 hover:bg-zinc-700 text-white ring-1 ring-zinc-700',
        'twitch' => 'bg-[#9146FF] hover:bg-[#7B2FF0] text-white',
    ];
@endphp

<div
    class="grid gap-2.5"
    x-data="{
        lastProvider: null,
        init() {
            try {
                const provider = window.localStorage.getItem('lastAuthProvider');
                this.lastProvider = @js(array_map(fn (IdentityProvider $provider): string => $provider->value, $providers)).includes(provider) ? provider : null;
            } catch (error) {
                this.lastProvider = null;
            }
        },
    }"
>
    @foreach ($providers as $provider)
        <a
            href="{{ $provider->getRedirectUri() }}"
            @class([
                'relative flex items-center justify-center gap-2.5 rounded-lg px-4 py-2.5 text-sm font-medium transition',
                $brand[$provider->value] ?? 'bg-zinc-800 hover:bg-zinc-700 text-white ring-1 ring-zinc-700',
            ])
        >
            @svg($provider->getIcon(), 'h-5 w-5')
            {{ __('he4rt::auth.continue_with', ['provider' => $provider->getLabel()]) }}
            <span
                x-cloak
                x-show="lastProvider === @js($provider->value)"
                class="absolute -top-2 right-2 z-10 rounded-full border border-zinc-600 bg-zinc-900/95 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wider text-zinc-300 shadow-sm"
            >
                {{ __('he4rt::auth.last_access') }}
            </span>
        </a>
    @endforeach
</div>
