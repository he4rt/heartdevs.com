<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Actions;

use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Economy\Trade\Exceptions\InvalidTradeException;
use He4rt\Economy\Trade\Models\Trade;

final class CancelTrade
{
    /**
     * @throws InvalidTradeException
     */
    public function handle(string $tradeId, string $initiatorCharacterId): Trade
    {
        $trade = Trade::query()->findOrFail($tradeId);

        if (!$trade->isPending()) {
            throw InvalidTradeException::notPending($tradeId);
        }

        if ($trade->initiator_character_id !== $initiatorCharacterId) {
            throw InvalidTradeException::notAuthorized();
        }

        $trade->update([
            'status' => TradeStatus::Cancelled,
            'resolved_at' => now(),
        ]);

        return $trade->fresh();
    }
}
