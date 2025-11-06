<?php

declare(strict_types=1);

namespace He4rt\Provider\Database\Factories;

use He4rt\Provider\Models\Provider;
use He4rt\Provider\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Token>
 */
final class TokenFactory extends Factory
{
    protected $model = Token::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'provider_id' => Provider::factory(),
            'access_token' => fake()->uuid(),
            'refresh_token' => fake()->uuid(),
            'expires_in' => fake()->randomNumber(4),
        ];
    }
}
