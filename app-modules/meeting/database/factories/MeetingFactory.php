<?php

declare(strict_types=1);

namespace He4rt\Meeting\Database\Factories;

use He4rt\Meeting\Models\Meeting;
use He4rt\Meeting\Models\MeetingType;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'meeting_type_id' => MeetingType::factory(),
            'admin_id' => User::factory(),
            'content' => 'Fake content',
            'starts_at' => fake()->dateTimeBetween('-1 hour'),
            'ends_at' => fake()->dateTimeBetween('+1 hour', '+2 hour'),
        ];
    }

    public function unfinished(): self
    {
        return $this->state(fn () => ['ends_at' => null, 'content' => null]);
    }
}
