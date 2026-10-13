<?php

declare(strict_types=1);

namespace He4rt\Streaming\Database\Factories;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StreamerSource> */
final class StreamerSourceFactory extends Factory
{
    protected $model = StreamerSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'streamer_id' => Streamer::factory(),
            'external_identity_id' => fn (array $attributes): string => ExternalIdentity::factory()->create([
                'model_id' => Streamer::query()->whereKey($attributes['streamer_id'])->sole()->user_id,
                'provider' => IdentityProvider::Twitch,
            ])->getKey(),
            'enabled' => true,
            'chat_reader' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }

    public function readingChat(ChatReader $reader = ChatReader::OwnAccount): static
    {
        return $this->state(['chat_reader' => $reader]);
    }
}
