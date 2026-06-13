<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Models\GithubContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GithubContribution>
 */
final class GithubContributionFactory extends Factory
{
    protected $model = GithubContribution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 1_000_000);

        return [
            'tenant_id' => Tenant::factory(),
            'repo' => 'he4rt/heartdevs.com',
            'actor_login' => fake()->userName(),
            'actor_id' => fake()->numberBetween(1, 9_999_999),
            'type' => ContributionType::Pr,
            'external_ref' => 'pr:'.$number,
            'target_ref' => null,
            'occurred_at' => now(),
            'metadata' => [],
        ];
    }

    public function bot(): self
    {
        return $this->state(fn (array $attributes): array => [
            'actor_login' => 'dependabot[bot]',
            'metadata' => [...$attributes['metadata'] ?? [], 'is_bot' => true],
        ]);
    }
}
