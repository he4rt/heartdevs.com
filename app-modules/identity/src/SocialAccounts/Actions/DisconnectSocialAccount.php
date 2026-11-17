<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Actions;

use He4rt\Identity\SocialAccounts\Exceptions\LastSignInMethod;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

final readonly class DisconnectSocialAccount
{
    /**
     * Two requests can each remove one of the last two accounts, so the check runs again under a lock of the user.
     */
    public function handle(SocialAccount $account): void
    {
        DB::transaction(function () use ($account): void {
            $user = User::query()->lockForUpdate()->findOrFail($account->user_id);

            throw_unless($user->hasSignInMethodBesides($account), LastSignInMethod::make());

            $account->delete();
        });
    }
}
