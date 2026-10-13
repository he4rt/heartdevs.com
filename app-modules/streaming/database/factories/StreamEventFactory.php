<?php

declare(strict_types=1);

namespace He4rt\Streaming\Database\Factories;

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StreamEvent> */
final class StreamEventFactory extends Factory
{
    protected $model = StreamEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $login = fake()->userName();

        return [
            'streamer_id' => Streamer::factory(),
            'external_identity_id' => fn (array $attributes): string => StreamerSourceFactory::new()
                ->createOne(['streamer_id' => $attributes['streamer_id']])
                ->external_identity_id,
            'stream_session_id' => null,
            'type' => StreamEventType::Follow,
            'actor_platform_id' => fake()->numerify('#########'),
            'actor_login' => $login,
            'actor_display_name' => $login,
            'details' => null,
            'source_event_id' => fake()->uuid(),
            'occurred_at' => now(),
        ];
    }

    public function forSource(StreamerSource $source): static
    {
        return $this->state([
            'streamer_id' => $source->streamer_id,
            'external_identity_id' => $source->external_identity_id,
        ]);
    }

    public function ofType(StreamEventType $type, ?StreamEventDetails $details = null): static
    {
        return $this->state([
            'type' => $type,
            'details' => $details,
        ]);
    }
}
