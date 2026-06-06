<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GithubRepository>
 */
final class GithubRepositoryFactory extends Factory
{
    protected $model = GithubRepository::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'full_name' => 'he4rt/'.fake()->unique()->slug(2),
            'enabled' => true,
            'last_backfilled_at' => null,
        ];
    }

    public function disabled(): self
    {
        return $this->state(['enabled' => false]);
    }

    public function backfilled(): self
    {
        return $this->state(['last_backfilled_at' => now()]);
    }
}
