<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
final class InteractionFactory extends Factory
{
    protected $model = Interaction::class;

    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'tenant_id' => Tenant::factory(),
            'type' => ActivityType::Article,
            'provider' => IdentityProvider::DevTo,
            'value_tier' => ValueTier::High,
            'coins_min' => 100,
            'coins_max' => 300,
            'status' => ActivityStatus::Pending,
            'occurred_at' => now(),
        ];
    }

    public function autoApproved(): self
    {
        return $this->state([
            'status' => ActivityStatus::AutoApproved,
            'type' => ActivityType::Engagement,
            'value_tier' => ValueTier::Low,
            'coins_min' => 1,
            'coins_max' => 3,
        ]);
    }

    public function approved(): self
    {
        return $this->state([
            'status' => ActivityStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function withEngagement(int $reactions = 0, int $comments = 0, int $bookmarks = 0): self
    {
        return $this->state([
            'metadata' => [
                'engagement_snapshot' => [
                    'reactions' => $reactions,
                    'comments' => $comments,
                    'bookmarks' => $bookmarks,
                ],
            ],
        ]);
    }
}
