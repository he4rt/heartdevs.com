<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\ValueObjects\DelasEligibilityResult;
use He4rt\Identity\User\Models\User;

/**
 * Responde em que situação a pessoa está em relação à tag.
 *
 * A ordem importa: quem tem a tag continua com ela mesmo bloqueada (o
 * bloqueio só impede novos pedidos), e o bloqueio vale mais que a espera.
 */
final readonly class DelasEligibility
{
    public function for(User $user): DelasEligibilityResult
    {
        $active = DelasTagRequest::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->first();

        if ($active instanceof DelasTagRequest) {
            return new DelasEligibilityResult(
                state: $active->status === DelasRequestStatus::Approved ? DelasEligibilityState::Member : DelasEligibilityState::Pending,
                request: $active,
            );
        }

        if ($this->isBlocked($user)) {
            return new DelasEligibilityResult(DelasEligibilityState::Blocked);
        }

        $nextAllowedAt = $this->nextAllowedAt($user);

        if ($nextAllowedAt instanceof CarbonInterface && $nextAllowedAt->isFuture()) {
            return new DelasEligibilityResult(DelasEligibilityState::Cooldown, nextAllowedAt: $nextAllowedAt);
        }

        return new DelasEligibilityResult(DelasEligibilityState::CanRequest);
    }

    public function hasTag(User $user): bool
    {
        return DelasTagRequest::query()
            ->where('user_id', $user->getKey())
            ->where('status', DelasRequestStatus::Approved)
            ->exists();
    }

    public function isBlocked(User $user): bool
    {
        return DelasRequesterBlock::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->exists();
    }

    /**
     * A partir de quando a pessoa pode pedir de novo, contado da última
     * rejeição ou remoção. Nulo se nunca houve uma.
     */
    private function nextAllowedAt(User $user): ?CarbonInterface
    {
        $lastDecision = DelasTagRequest::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [DelasRequestStatus::Rejected, DelasRequestStatus::Revoked])
            ->max('decided_at');

        if ($lastDecision === null) {
            return null;
        }

        return CarbonImmutable::parse($lastDecision)->addDays(config()->integer('delas.request_cooldown_days'));
    }
}
