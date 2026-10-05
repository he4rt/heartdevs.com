<?php

declare(strict_types=1);

namespace He4rt\Streaming\Database\Factories;

use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StreamSession> */
final class StreamSessionFactory extends Factory
{
    protected $model = StreamSession::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'streamer_id' => Streamer::factory(),
            'external_identity_id' => fn (array $attributes): string => StreamerSourceFactory::new()
                ->createOne(['streamer_id' => $attributes['streamer_id']])
                ->external_identity_id,
            'platform_stream_id' => fake()->unique()->numerify('##########'),
            'title' => fake()->sentence(4),
            'category' => 'Software and Game Development',
            'started_at' => now()->subHour(),
            'ended_at' => null,
        ];
    }

    public function forSource(StreamerSource $source): static
    {
        return $this->state([
            'streamer_id' => $source->streamer_id,
            'external_identity_id' => $source->external_identity_id,
        ]);
    }

    public function ended(): static
    {
        return $this->state(['ended_at' => now()]);
    }
}
