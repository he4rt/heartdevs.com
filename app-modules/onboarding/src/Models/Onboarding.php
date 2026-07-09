<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Models;

use Carbon\CarbonInterface;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Database\Factories\OnboardingFactory;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $user_id
 * @property OnboardingType $type
 * @property OnboardingStatus $status
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $paused_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Table(name: 'onboardings')]
#[UseFactory(factoryClass: OnboardingFactory::class)]
final class Onboarding extends Model
{
    /** @use HasFactory<OnboardingFactory> */
    use HasFactory;
    use HasUuids;

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OnboardingStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(OnboardingStep::class);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => OnboardingType::class,
            'status' => OnboardingStatus::class,
            'completed_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }
}
