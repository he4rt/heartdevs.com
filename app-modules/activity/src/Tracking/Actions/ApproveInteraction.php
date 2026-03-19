<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Events\InteractionApproved;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Economy\Actions\Credit;
use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Gamification\Character\Models\Character;

final readonly class ApproveInteraction
{
    public function __construct(
        private CalculateReward $calculateReward,
    ) {}

    public function handle(Interaction $interaction, ?int $peerReviewBase = null): Interaction
    {
        $reward = $this->calculateReward->handle($interaction, $peerReviewBase);

        $character = Character::query()->findOrFail($interaction->character_id);
        $wallet = $character->getOrCreateWallet();

        resolve(Credit::class)->handle(new CreditDTO(
            walletId: $wallet->id,
            amount: $reward['coins_awarded'],
            referenceType: Interaction::class,
            referenceId: $interaction->id,
            description: 'Reward: '.$interaction->type->value,
        ));

        $character->increment('experience', $reward['xp_awarded']);

        $interaction->update([
            'status' => ActivityStatus::Approved,
            'reviewed_at' => now(),
        ]);

        event(new InteractionApproved($interaction->fresh()));

        return $interaction->fresh();
    }
}
