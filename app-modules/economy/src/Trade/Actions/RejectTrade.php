<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Actions;

use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Economy\Trade\Exceptions\InvalidTradeException;
use He4rt\Economy\Trade\Models\Trade;

final class RejectTrade
{
    /**
     * @throws InvalidTradeException
     */
    public function handle(string $tradeId, string $receiverCharacterId): Trade
    {
        $trade = Trade::query()->findOrFail($tradeId);

        if (!$trade->isPending()) {
            throw InvalidTradeException::notPending($tradeId);
        }

        if ($trade->receiver_character_id !== $receiverCharacterId) {
            throw InvalidTradeException::notAuthorized();
        }

        $trade->update([
            'status' => TradeStatus::Rejected,
            'resolved_at' => now(),
        ]);

        return $trade->fresh();
    }
}
