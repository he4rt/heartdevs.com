<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voice>
 */
final class VoiceFactory extends Factory
{
    protected $model = Voice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'external_identity_id' => ExternalIdentity::factory(),
            'channel_name' => fake()->word(),
            'channel_id' => (string) fake()->randomNumber(9),
            'state' => VoicePresenceEnum::Joined->value,
            'obtained_experience' => 0,
            'occurred_at' => now()->utc(),
        ];
    }

    public function joined(): self
    {
        return $this->state(['state' => VoicePresenceEnum::Joined->value]);
    }

    public function left(): self
    {
        return $this->state(['state' => VoicePresenceEnum::Left->value]);
    }
}
