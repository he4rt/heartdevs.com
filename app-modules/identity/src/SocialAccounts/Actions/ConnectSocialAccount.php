<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Actions;

use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\Exceptions\SocialAccountTaken;
use He4rt\Identity\SocialAccounts\Exceptions\SocialProviderAlreadyConnected;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;

final readonly class ConnectSocialAccount
{
    public function handle(User $user, SocialProvider $provider, SocialUser $socialUser): SocialAccount
    {
        $linkedAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $socialUser->providerUserId)
            ->first();

        if ($linkedAccount instanceof SocialAccount) {
            throw_unless($linkedAccount->user_id === $user->id, SocialAccountTaken::for($provider));

            return $linkedAccount;
        }

        $hasAccountOfProvider = $user->socialAccounts()->where('provider', $provider)->exists();

        throw_if($hasAccountOfProvider, SocialProviderAlreadyConnected::for($provider));

        return $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_user_id' => $socialUser->providerUserId,
        ]);
    }
}
