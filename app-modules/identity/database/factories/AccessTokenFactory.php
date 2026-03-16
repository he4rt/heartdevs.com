<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\ExternalIdentity\Models\AccessToken;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessToken>
 */
final class AccessTokenFactory extends Factory
{
    protected $model = AccessToken::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'provider_id' => ExternalIdentity::factory(),
            'access_token' => fake()->uuid(),
            'refresh_token' => fake()->uuid(),
            'expires_in' => fake()->randomNumber(4),
        ];
    }
}
