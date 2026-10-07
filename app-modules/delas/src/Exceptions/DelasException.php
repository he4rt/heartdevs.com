<?php

declare(strict_types=1);

namespace He4rt\Delas\Exceptions;

use Carbon\CarbonInterface;
use Exception;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regra de negócio da He4rt Delas recusada. A mensagem é traduzida e pode ser
 * mostrada direto na interface.
 */
final class DelasException extends Exception
{
    public static function alreadyHasActiveRequest(): self
    {
        return new self(__('delas::exceptions.already_has_active_request'), Response::HTTP_CONFLICT);
    }

    public static function inCooldown(CarbonInterface $nextAllowedAt): self
    {
        return new self(
            __('delas::exceptions.in_cooldown', [
                'date' => $nextAllowedAt->timezone(config('app.display_timezone'))->format('d/m'),
            ]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function blocked(): self
    {
        return new self(__('delas::exceptions.blocked'), Response::HTTP_FORBIDDEN);
    }

    public static function invalidTransition(DelasRequestStatus $from, DelasRequestStatus $to): self
    {
        return new self(
            __('delas::exceptions.invalid_transition', ['from' => $from->getLabel(), 'to' => $to->getLabel()]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public static function cannotDecideOwn(): self
    {
        return new self(__('delas::exceptions.cannot_decide_own'), Response::HTTP_FORBIDDEN);
    }

    public static function reasonRequired(): self
    {
        return new self(__('delas::exceptions.reason_required'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function cannotBlockTeam(): self
    {
        return new self(__('delas::exceptions.cannot_block_team'), Response::HTTP_FORBIDDEN);
    }

    public static function alreadyBlocked(): self
    {
        return new self(__('delas::exceptions.already_blocked'), Response::HTTP_CONFLICT);
    }

    public static function blockAlreadyLifted(): self
    {
        return new self(__('delas::exceptions.block_already_lifted'), Response::HTTP_CONFLICT);
    }

    public static function cannotUnblockOthers(): self
    {
        return new self(__('delas::exceptions.cannot_unblock_others'), Response::HTTP_FORBIDDEN);
    }

    public static function alreadyHasTag(): self
    {
        return new self(__('delas::exceptions.already_has_tag'), Response::HTTP_CONFLICT);
    }

    public static function hasNoTag(): self
    {
        return new self(__('delas::exceptions.has_no_tag'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function cannotManageRole(): self
    {
        return new self(__('delas::exceptions.cannot_manage_role'), Response::HTTP_FORBIDDEN);
    }

    public static function moderatorNeedsTag(): self
    {
        return new self(__('delas::exceptions.moderator_needs_tag'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function alreadyModerator(): self
    {
        return new self(__('delas::exceptions.already_moderator'), Response::HTTP_CONFLICT);
    }

    public static function reasonNotCorrectable(): self
    {
        return new self(__('delas::exceptions.reason_not_correctable'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function cannotCorrectReason(int $hours): self
    {
        return new self(__('delas::exceptions.cannot_correct_reason', ['hours' => $hours]), Response::HTTP_FORBIDDEN);
    }

    public static function reasonUnchanged(): self
    {
        return new self(__('delas::exceptions.reason_unchanged'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function notModerator(): self
    {
        return new self(__('delas::exceptions.not_moderator'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
