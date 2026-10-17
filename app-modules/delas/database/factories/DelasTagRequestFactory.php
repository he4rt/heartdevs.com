<?php

declare(strict_types=1);

namespace He4rt\Delas\Database\Factories;

use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DelasTagRequest> */
final class DelasTagRequestFactory extends Factory
{
    protected $model = DelasTagRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => DelasRequestStatus::Pending,
            'requested_at' => now(),
            'decided_by' => null,
            'decided_at' => null,
            'decision_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => DelasRequestStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->decided(DelasRequestStatus::Approved);
    }

    public function rejected(?string $reason = 'Perfil sem informações suficientes.'): static
    {
        return $this->decided(DelasRequestStatus::Rejected, $reason);
    }

    public function revoked(?string $reason = 'A própria pessoa pediu a remoção.'): static
    {
        return $this->decided(DelasRequestStatus::Revoked, $reason);
    }

    private function decided(DelasRequestStatus $status, ?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'decided_by' => User::factory(),
            'decided_at' => now(),
            'decision_reason' => $reason,
        ]);
    }
}
