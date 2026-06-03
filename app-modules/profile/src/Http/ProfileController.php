<?php

declare(strict_types=1);

namespace He4rt\Profile\Http;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ProfileController
{
    private const array PROVIDER_URLS = [
        'github' => 'https://github.com/%s',
        'discord' => 'https://discord.com/users/%s',
        'twitch' => 'https://twitch.tv/%s',
        'devto' => 'https://dev.to/%s',
    ];

    public function show(Request $request, string $username): Factory|View
    {
        $tenant = Tenant::query()
            ->where('domain', $request->getHost())
            ->firstOrFail();

        $user = User::query()
            ->where('username', $username)
            ->first();

        abort_if($user === null, 404);

        $profile = Profile::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_if($profile === null, 404);

        $user->load(['character.badges', 'providers', 'address']);

        $connectedAccounts = $this->buildConnectedAccounts($profile, $user);

        return view('profile::public-profile', [
            'user' => $user,
            'profile' => $profile,
            'tenant' => $tenant,
            'connectedAccounts' => $connectedAccounts,
        ]);
    }

    private function buildConnectedAccounts(Profile $profile, User $user): Collection
    {
        $accounts = collect();

        if ($profile->social_links) {
            foreach ($profile->social_links as $platform => $url) {
                $accounts->push([
                    'provider' => $platform,
                    'label' => $platform,
                    'url' => $url,
                ]);
            }
        }

        foreach ($user->providers as $provider) {
            $name = $provider->provider instanceof IdentityProvider
                ? $provider->provider->value
                : $provider->provider;

            $template = self::PROVIDER_URLS[$name] ?? null;

            if ($template) {
                $accounts->push([
                    'provider' => $name,
                    'label' => $name,
                    'url' => sprintf($template, $provider->external_account_id),
                ]);
            }
        }

        return $accounts;
    }
}
