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

    private const array SOCIAL_ICONS = [
        'whatsapp' => 'fa-brands fa-whatsapp',
        'linkedin' => 'fa-brands fa-linkedin-in',
        'github' => 'fa-brands fa-github',
        'devto' => 'fa-brands fa-dev',
        'instagram' => 'fa-brands fa-instagram',
        'twitter' => 'fa-brands fa-twitter',
        'youtube' => 'fa-brands fa-youtube',
        'website' => 'fas fa-globe',
        'bluesky' => 'fa-brands fa-bluesky',
    ];

    public function show(Request $request, string $username): Factory|View
    {
        $tenant = Tenant::query()
            ->where('domain', $request->getHost())
            ->where('active', true)
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

        $user->load([
            'character.badges',
            'providers' => fn ($query) => $query->where('tenant_id', $tenant->id),
            'address',
        ]);

        $connectedAccounts = $this->buildConnectedAccounts($profile, $user);

        $resumeUrl = $profile->getFirstMediaUrl('resume') ?: null;

        $projects = $profile->projects()->orderBy('sort_order')->get();

        $pullRequests = $profile->pullRequests()->latest()->get();

        return view('profile::public-profile', [
            'user' => $user,
            'profile' => $profile,
            'tenant' => $tenant,
            'connectedAccounts' => $connectedAccounts,
            'socialIcons' => self::SOCIAL_ICONS,
            'projects' => $projects,
            'pullRequests' => $pullRequests,
            'resumeUrl' => $resumeUrl,
        ]);
    }

    private function buildConnectedAccounts(Profile $profile, User $user): Collection
    {
        $accounts = collect();
        $seen = [];

        if ($profile->social_links) {
            foreach ($profile->social_links as $platform => $url) {
                $accounts->push([
                    'provider' => $platform,
                    'label' => $platform,
                    'url' => $url,
                ]);
                $seen[] = $platform;
            }
        }

        foreach ($user->providers as $provider) {
            $name = $provider->provider instanceof IdentityProvider
                ? $provider->provider->value
                : $provider->provider;

            if (in_array($name, $seen, true)) {
                continue;
            }

            $template = self::PROVIDER_URLS[$name] ?? null;

            if ($template && $provider->external_account_id) {
                $accounts->push([
                    'provider' => $name,
                    'label' => $name,
                    'url' => sprintf($template, $provider->external_account_id),
                ]);
                $seen[] = $name;
            }
        }

        return $accounts;
    }
}
