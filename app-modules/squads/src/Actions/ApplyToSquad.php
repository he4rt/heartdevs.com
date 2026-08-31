<?php

declare(strict_types=1);

namespace He4rt\Squads\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Squads\Enums\ApplicationStatus;
use He4rt\Squads\Exceptions\ApplicationAlreadyPending;
use He4rt\Squads\Exceptions\NotAptForSquads;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadApplication;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Opens a candidacy to a squad, the one flow this module conducts.
 *
 * Entry is gated on APTO: the applicant must have completed the `Squads`
 * onboarding. The gate is read from `onboarding`; this module never owns that
 * state. Whoever catches `NotAptForSquads` points the person at the onboarding.
 *
 * The captain's decision is a separate Action; here the application is only
 * opened, always as `pending`.
 */
final readonly class ApplyToSquad
{
    public function __construct(
        private OnboardingCompletionGate $onboardingGate,
    ) {}

    public function handle(User $applicant, Squad $squad, ?string $message = null): SquadApplication
    {
        throw_unless(
            $this->onboardingGate->isCompleted($applicant, OnboardingType::Squads),
            NotAptForSquads::for($applicant),
        );

        try {
            // The "one pending application per squad" rule is the partial unique
            // index, not a prior SELECT: two simultaneous applications would both
            // read "none pending" and both insert. The insert decides; a rejected
            // one falls out of the index, so re-applying later is allowed.
            /** @var SquadApplication $application */
            $application = SquadApplication::query()->create([
                'squad_id' => $squad->id,
                'user_id' => $applicant->id,
                'status' => ApplicationStatus::Pending,
                'message' => $message,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ApplicationAlreadyPending::for($squad, $applicant);
        }

        return $application;
    }
}
