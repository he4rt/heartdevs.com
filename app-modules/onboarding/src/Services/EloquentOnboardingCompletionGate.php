<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Services;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;

final class EloquentOnboardingCompletionGate implements OnboardingCompletionGate
{
    public function isCompleted(User $user, OnboardingType $type): bool
    {
        return Onboarding::query()
            ->whereBelongsTo($user)
            ->where('type', $type)
            ->where('status', OnboardingStatus::Completed)
            ->exists();
    }

    public function completedAt(User $user, OnboardingType $type): ?CarbonInterface
    {
        return Onboarding::query()
            ->whereBelongsTo($user)
            ->where('type', $type)
            ->where('status', OnboardingStatus::Completed)
            ->first(['completed_at'])
            ?->completed_at;
    }
}
