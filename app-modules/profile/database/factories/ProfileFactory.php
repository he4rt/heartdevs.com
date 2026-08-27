<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => null,
            'birthdate' => null,
            'about' => null,
            'headline' => null,
            'seniority_level' => null,
            'years_experience' => null,
            'social_links' => null,
            'available_for_proposals' => false,
            'start_availability' => null,
        ];
    }

    /**
     * Reuse the profile that {@see \He4rt\Identity\User\Observers\UserObserver}
     * already created for the user instead of inserting a second row, which
     * would violate the (now tenant-free) unique constraint on `user_id`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = []): Profile
    {
        $userId = $attributes['user_id'] ?? null;

        if ($userId !== null) {
            $existing = Profile::query()->where('user_id', $userId)->first();

            if ($existing !== null) {
                return $existing->fill($attributes);
            }
        }

        return parent::newModel($attributes);
    }

    public function complete(): self
    {
        return $this->state(fn (): array => [
            'nickname' => fake()->userName(),
            'birthdate' => fake()->date(),
            'about' => fake()->paragraph(),
            'headline' => fake()->jobTitle(),
            'seniority_level' => fake()->randomElement(SeniorityLevel::cases()),
            'years_experience' => fake()->numberBetween(1, 20),
            'social_links' => [
                SocialPlatform::Instagram->value => fake()->userName(),
                SocialPlatform::Website->value => fake()->url(),
            ],
            'available_for_proposals' => true,
            'start_availability' => fake()->randomElement(StartAvailability::cases()),
        ]);
    }

    public function withSkills(int $count = 3): self
    {
        return $this->afterCreating(static function (Profile $profile) use ($count): void {
            ProfileSkill::factory()
                ->count($count)
                ->for($profile)
                ->create();
        });
    }
}
