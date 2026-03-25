<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Events\InteractionApproved;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Economy\Actions\Credit;
use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Support\Facades\DB;

final readonly class ApproveInteraction
{
    public function __construct(
        private CalculateReward $calculateReward,
    ) {}

    public function handle(Interaction $interaction, ?int $peerReviewBase = null): Interaction
    {
        if ($interaction->status !== ActivityStatus::Pending) {
            return $interaction;
        }

        return DB::transaction(function () use ($interaction, $peerReviewBase): Interaction {
            $locked = Interaction::query()
                ->where('id', $interaction->id)
                ->where('status', ActivityStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return $interaction->fresh();
            }

            $reward = $this->calculateReward->handle($locked, $peerReviewBase);

            $character = Character::query()->findOrFail($locked->character_id);
            $wallet = $character->getOrCreateWallet();

            resolve(Credit::class)->handle(new CreditDTO(
                walletId: $wallet->id,
                amount: $reward['coins_awarded'],
                referenceType: Interaction::class,
                referenceId: $locked->id,
                description: 'Reward: '.$locked->type->value,
            ));

            $character->increment('experience', $reward['xp_awarded']);

            $locked->update([
                'status' => ActivityStatus::Approved,
                'reviewed_at' => now(),
            ]);

            event(new InteractionApproved($locked->fresh()));

            return $locked->fresh();
        });
    }
}
