<?php

declare(strict_types=1);

namespace He4rt\Streaming\Database\Factories;

use He4rt\Identity\Database\Factories\UserFactory;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Streamer> */
final class StreamerFactory extends Factory
{
    protected $model = Streamer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $token = Str::lower(Str::random(32));

        return [
            'user_id' => UserFactory::new()->streamer(),
            'status' => StreamerStatus::Active,
            'overlay_token' => $token,
            'overlay_token_hash' => hash('sha256', $token),
            'settings' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['status' => StreamerStatus::Disabled]);
    }

    public function withToken(string $token): static
    {
        return $this->state([
            'overlay_token' => $token,
            'overlay_token_hash' => hash('sha256', $token),
        ]);
    }
}
