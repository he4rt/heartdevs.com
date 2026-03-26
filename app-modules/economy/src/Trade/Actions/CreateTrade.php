<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Actions;

use He4rt\Economy\Trade\DTOs\CreateTradeDTO;
use He4rt\Economy\Trade\Enums\TradeDirection;
use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Economy\Trade\Exceptions\InvalidTradeException;
use He4rt\Economy\Trade\Exceptions\TradeItemNotValidException;
use He4rt\Economy\Trade\Models\Trade;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CreateTrade
{
    /**
     * @throws InvalidTradeException
     * @throws TradeItemNotValidException
     */
    public function handle(CreateTradeDTO $dto): Trade
    {
        if ($dto->initiatorCharacterId === $dto->receiverCharacterId) {
            throw InvalidTradeException::selfTrade();
        }

        $this->validateItems($dto->offeredItemIds, $dto->initiatorCharacterId);
        $this->validateItems($dto->requestedItemIds, $dto->receiverCharacterId);

        return DB::transaction(function () use ($dto): Trade {
            $trade = Trade::query()->create([
                'tenant_id' => $dto->tenantId,
                'initiator_character_id' => $dto->initiatorCharacterId,
                'receiver_character_id' => $dto->receiverCharacterId,
                'status' => TradeStatus::Pending,
            ]);

            foreach ($dto->offeredItemIds as $characterItemId) {
                $trade->items()->create([
                    'character_item_id' => $characterItemId,
                    'direction' => TradeDirection::Offer,
                ]);
            }

            foreach ($dto->requestedItemIds as $characterItemId) {
                $trade->items()->create([
                    'character_item_id' => $characterItemId,
                    'direction' => TradeDirection::Request,
                ]);
            }

            return $trade->load('items');
        });
    }

    /**
     * @param  array<int, string>  $characterItemIds
     *
     * @throws TradeItemNotValidException
     */
    private function validateItems(array $characterItemIds, string $expectedCharacterId): void
    {
        foreach ($characterItemIds as $characterItemId) {
            $characterItem = CharacterItem::query()
                ->with('item')
                ->findOrFail($characterItemId);

            if ($characterItem->character_id !== $expectedCharacterId) {
                throw TradeItemNotValidException::notOwned($characterItemId);
            }

            if (!$characterItem->item->is_tradeable) {
                throw TradeItemNotValidException::notTradeable($characterItemId);
            }

            $isEquipped = CharacterEquipment::query()
                ->where('character_item_id', $characterItemId)
                ->exists();

            if ($isEquipped) {
                throw TradeItemNotValidException::currentlyEquipped($characterItemId);
            }

            $inPendingTrade = Trade::query()
                ->where('status', TradeStatus::Pending)
                ->whereHas('items', fn (Builder $q) => $q->where('character_item_id', $characterItemId))
                ->exists();

            if ($inPendingTrade) {
                throw TradeItemNotValidException::inPendingTrade($characterItemId);
            }
        }
    }
}
