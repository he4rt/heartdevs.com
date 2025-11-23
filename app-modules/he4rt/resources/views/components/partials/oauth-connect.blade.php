@php
    use He4rt\Authentication\Enums\OAuthProviderEnum;
    /** @var OAuthProviderEnum[] $providers */
@endphp

@props([
    'providers',
    'tenantSlug',
])
<div>
    <x-he4rt::text class="mb-4 text-center">Sign in to your account to continue</x-he4rt::text>

    <div class="grid space-y-3">
        @foreach ($providers as $provider)
            @if ($provider->isEnabled())
                <x-he4rt::button
                    :href="$provider->getRedirectUri($tenantSlug)"
                    :icon="$provider->getIcon()"
                    icon-position="leading"
                >
                    Continue with {{ $provider->getLabel() }}
                </x-he4rt::button>
            @endif
        @endforeach
    </div>
    <div class="relative mt-5">
        <div class="absolute inset-0 flex items-center">
            <hr class="w-full dark:border-gray-300" />
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="px-2 dark:bg-gray-900">Or continue with</span>
        </div>
    </div>
</div>
