<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Contracts;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Enums\OnboardingType;

interface OnboardingCompletionGate
{
    public function isCompleted(User $user, OnboardingType $type): bool;

    public function completedAt(User $user, OnboardingType $type): ?CarbonInterface;
}
