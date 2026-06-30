<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Models;

use Carbon\CarbonInterface;
use He4rt\Onboarding\Database\Factories\OnboardingStepFactory;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $onboarding_id
 * @property string $step_key
 * @property OnboardingStepStatus $status
 * @property array<string, mixed>|null $data
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table('onboarding_steps')]
#[UseFactory(OnboardingStepFactory::class)]
final class OnboardingStep extends Model
{
    /** @use HasFactory<OnboardingStepFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Onboarding, $this> */
    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(Onboarding::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => OnboardingStepStatus::class,
            'data' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
