<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Events\InteractionTracked;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Economy\Actions\Credit;
use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Gamification\Character\Models\Character;

final readonly class TrackActivity
{
    public function __construct(
        private ClassifyActivity $classifyActivity,
        private CalculateReward $calculateReward,
    ) {}

    public function handle(TrackActivityDTO $dto): ?Interaction
    {
        if ($dto->externalRef !== null) {
            $exists = Interaction::query()
                ->where('external_ref', $dto->externalRef)
                ->exists();

            if ($exists) {
                return null;
            }
        }

        $classification = $this->classifyActivity->handle($dto->type);

        $interaction = Interaction::query()->create([
            'character_id' => $dto->characterId,
            'type' => $dto->type,
            'provider' => $dto->provider,
            'value_tier' => $classification['tier'],
            'coins_min' => $classification['coins_min'],
            'coins_max' => $classification['coins_max'],
            'status' => $classification['status'],
            'source_type' => $dto->sourceType,
            'source_id' => $dto->sourceId,
            'external_ref' => $dto->externalRef,
            'metadata' => $dto->metadata,
            'occurred_at' => $dto->occurredAt,
        ]);

        if ($classification['status'] === ActivityStatus::AutoApproved) {
            $reward = $this->calculateReward->handle($interaction);

            $character = Character::query()->findOrFail($dto->characterId);
            $wallet = $character->getOrCreateWallet();

            resolve(Credit::class)->handle(new CreditDTO(
                walletId: $wallet->id,
                amount: $reward['coins_awarded'],
                referenceType: Interaction::class,
                referenceId: $interaction->id,
                description: 'Reward: '.$dto->type->value,
            ));

            $character->increment('experience', $reward['xp_awarded']);
        }

        event(new InteractionTracked($interaction));

        return $interaction;
    }
}
