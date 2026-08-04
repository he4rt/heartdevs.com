<?php

declare(strict_types=1);

namespace He4rt\Onboarding\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Exceptions\PrerequisiteNotMetException;
use He4rt\Onboarding\Models\Onboarding;
use Illuminate\Support\Facades\DB;

final readonly class StartOnboarding
{
    public function __construct(
        private OnboardingCompletionGate $gate,
    ) {}

    public function handle(User $user, OnboardingType $type): Onboarding
    {
        // Resolve o flow antes de qualquer escrita: tipo sem handler falha alto,
        // sem deixar um onboarding órfão (em vez de persistir um estado travado).
        $flow = $type->handler();

        // Pré-requisitos inter-tipo (ex.: Squads exige Welcome concluído) são
        // validados via gate antes de qualquer escrita.
        foreach ($flow->prerequisites() as $prerequisite) {
            throw_unless(
                $this->gate->isCompleted($user, $prerequisite),
                PrerequisiteNotMetException::class,
                type: $type,
                prerequisite: $prerequisite,
            );
        }

        return DB::transaction(static function () use ($user, $type, $flow): Onboarding {
            $onboarding = Onboarding::query()->firstOrCreate(
                [
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
