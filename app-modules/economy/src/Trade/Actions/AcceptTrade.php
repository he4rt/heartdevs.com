<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Actions;

use He4rt\Economy\Trade\Enums\TradeDirection;
use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Economy\Trade\Exceptions\InvalidTradeException;
use He4rt\Economy\Trade\Exceptions\TradeItemNotValidException;
use He4rt\Economy\Trade\Models\Trade;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use Illuminate\Support\Facades\DB;

final class AcceptTrade
{
    /**
     * @throws InvalidTradeException
     * @throws TradeItemNotValidException
     */
    public function handle(string $tradeId, string $receiverCharacterId): Trade
    {
        $trade = Trade::query()->with('items')->findOrFail($tradeId);

        if (!$trade->isPending()) {
            throw InvalidTradeException::notPending($tradeId);
        }

        if ($trade->receiver_character_id !== $receiverCharacterId) {
            throw InvalidTradeException::notAuthorized();
        }

        return DB::transaction(function () use ($trade): Trade {
            foreach ($trade->items as $tradeItem) {
                $characterItem = CharacterItem::query()->findOrFail($tradeItem->character_item_id);

                $expectedOwner = $tradeItem->direction === TradeDirection::Offer
                    ? $trade->initiator_character_id
                    : $trade->receiver_character_id;

                if ($characterItem->character_id !== $expectedOwner) {
                    throw TradeItemNotValidException::noLongerValid($tradeItem->character_item_id);
                }

                $isEquipped = CharacterEquipment::query()
                    ->where('character_item_id', $tradeItem->character_item_id)
                    ->exists();

                if ($isEquipped) {
                    throw TradeItemNotValidException::currentlyEquipped($tradeItem->character_item_id);
                }
            }

            foreach ($trade->items as $tradeItem) {
                $newOwner = $tradeItem->direction === TradeDirection::Offer
                    ? $trade->receiver_character_id
                    : $trade->initiator_character_id;

                CharacterItem::query()
                    ->where('id', $tradeItem->character_item_id)
                    ->update([
                        'character_id' => $newOwner,
                        'acquired_via' => 'trade',
                        'acquired_at' => now(),
                    ]);
            }

            $trade->update([
                'status' => TradeStatus::Accepted,
                'resolved_at' => now(),
            ]);

            return $trade->fresh('items');
        });
    }
}
