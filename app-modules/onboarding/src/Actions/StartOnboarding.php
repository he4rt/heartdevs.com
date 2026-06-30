<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Support\Facades\DB;

final class StartOnboarding
{
    public function handle(Tenant $tenant, User $user, OnboardingType $type): Onboarding
    {
        // Resolve o flow antes de qualquer escrita: tipo sem handler falha alto,
        // sem deixar um onboarding órfão (em vez de persistir um estado travado).
        $flow = $type->handler();

        return DB::transaction(static function () use ($tenant, $user, $type, $flow): Onboarding {
            $onboarding = Onboarding::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->getKey(),
                    'user_id' => $user->getKey(),
                    'type' => $type,
                ],
                ['status' => OnboardingStatus::InProgress],
            );

            $onboarding->steps()->firstOrCreate(
                ['step_key' => $flow->steps()[0]],
                ['status' => OnboardingStepStatus::Pending],
            );

            return $onboarding;
        });
    }
}
