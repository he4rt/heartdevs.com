<?php

declare(strict_types=1);

namespace He4rt\Delas\Database\Factories;

use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DelasRequesterBlock> */
final class DelasRequesterBlockFactory extends Factory
{
    protected $model = DelasRequesterBlock::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blocked_by' => User::factory(),
            'reason' => 'Pedidos repetidos com intenção de tumultuar a fila.',
            'blocked_at' => now(),
            'lifted_by' => null,
            'lifted_at' => null,
            'lift_reason' => null,
        ];
    }

    public function lifted(string $reason = 'Situação esclarecida com o comitê.'): static
    {
        return $this->state(fn (): array => [
            'lifted_by' => User::factory(),
            'lifted_at' => now(),
            'lift_reason' => $reason,
        ]);
    }
}
