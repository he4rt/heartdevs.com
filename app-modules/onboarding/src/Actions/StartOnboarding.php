<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Contracts\OnboardingFlow;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Support\Facades\DB;

final class StartOnboarding
{
    public function handle(Tenant $tenant, User $user, OnboardingType $type): Onboarding
    {
        return DB::transaction(static function () use ($tenant, $user, $type): Onboarding {
            $onboarding = Onboarding::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'user_id' => $user->getKey(),
                    'type' => $type,
                ],
                ['status' => OnboardingStatus::InProgress],
            );

            $flow = $type->handler();

            if ($flow instanceof OnboardingFlow) {
                $onboarding->steps()->firstOrCreate(
                    ['step_key' => $flow->steps()[0]],
                    ['status' => OnboardingStepStatus::Pending],
                );
            }

            return $onboarding;
        });
    }
}
