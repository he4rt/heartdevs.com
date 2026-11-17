<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Actions;

use He4rt\Identity\SocialAccounts\DTOs\SocialUser;
use He4rt\Identity\SocialAccounts\Exceptions\RegistrationClosed;
use He4rt\Identity\SocialAccounts\Exceptions\SocialEmailMissing;
use He4rt\Identity\SocialAccounts\Exceptions\SocialEmailNotVerified;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds the user of a provider account: the linked user, the user with the same verified email, or a new user.
 */
final readonly class SignInWithSocialAccount
{
    public function __construct(private ConnectSocialAccount $connectSocialAccount) {}

    public function handle(SocialProvider $provider, SocialUser $socialUser, bool $canRegister): User
    {
        $linkedAccount = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', $socialUser->providerUserId)
            ->first();

        if ($linkedAccount instanceof SocialAccount) {
            return $linkedAccount->user;
        }

        throw_if($socialUser->email === null, SocialEmailMissing::for($provider));

        $email = Str::lower($socialUser->email);
        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User) {
            throw_unless($socialUser->emailVerified, SocialEmailNotVerified::for($provider));

            $this->connectSocialAccount->handle($user, $provider, $socialUser);

            return $user;
        }

        throw_unless($canRegister, RegistrationClosed::make());

        return DB::transaction(function () use ($provider, $socialUser, $email): User {
            $user = User::query()->create([
                'name' => $socialUser->name,
                'email' => $email,
                'email_verified_at' => $socialUser->emailVerified ? now() : null,
            ]);

            $this->connectSocialAccount->handle($user, $provider, $socialUser);

            return $user;
        });
    }
}
