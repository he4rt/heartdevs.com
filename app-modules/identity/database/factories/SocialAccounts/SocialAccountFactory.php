<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories\SocialAccounts;

use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialAccount> */
final class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => SocialProvider::GitHub,
            'provider_user_id' => (string) fake()->unique()->randomNumber(8),
        ];
    }

    public function provider(SocialProvider $provider): self
    {
        return $this->state(['provider' => $provider]);
    }
}
