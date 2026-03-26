<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\Actions;

use He4rt\Economy\Actions\Debit;
use He4rt\Economy\DTOs\DebitDTO;
use He4rt\Economy\Exceptions\InsufficientBalanceException;
use He4rt\Economy\Shop\DTOs\PurchaseItemDTO;
use He4rt\Economy\Shop\Exceptions\ItemAlreadyOwnedException;
use He4rt\Economy\Shop\Exceptions\ItemNotAvailableException;
use He4rt\Economy\Shop\Exceptions\ItemOutOfStockException;
use He4rt\Economy\Shop\Exceptions\LevelRequirementNotMetException;
use He4rt\Economy\Shop\Models\ShopListing;
use He4rt\Gamification\Character\Inventory\Actions\AddItemToInventory;
use He4rt\Gamification\Character\Inventory\DTOs\AddItemToInventoryDTO;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseItem
{
    public function __construct(
        private Debit $debit,
        private AddItemToInventory $addItemToInventory,
    ) {}

    /**
     * @throws ItemNotAvailableException
     * @throws ItemOutOfStockException
     * @throws ItemAlreadyOwnedException
     * @throws LevelRequirementNotMetException
     * @throws InsufficientBalanceException
     */
    public function handle(PurchaseItemDTO $dto): CharacterItem
    {
        $character = Character::query()->findOrFail($dto->characterId);
        $listing = ShopListing::query()->with('item')->findOrFail($dto->shopListingId);

        if (!$listing->isAvailable()) {
            if ($listing->stock !== null && $listing->stock <= 0) {
                throw ItemOutOfStockException::forListing($listing->id);
            }

            throw ItemNotAvailableException::forListing($listing->id);
        }

        $item = $listing->item;

        if ($item->level_required > 0 && $character->level < $item->level_required) {
            throw LevelRequirementNotMetException::forCharacter($character->level, $item->level_required);
        }

        $alreadyOwns = CharacterItem::query()
            ->where('character_id', $character->id)
            ->where('item_id', $item->id)
            ->exists();

        if ($alreadyOwns) {
            throw ItemAlreadyOwnedException::forCharacter($character->id, $item->id);
        }

        $wallet = $character->getOrCreateWallet();

        return DB::transaction(function () use ($wallet, $listing, $character, $item): CharacterItem {
            $this->debit->handle(new DebitDTO(
                walletId: $wallet->id,
                amount: $listing->price,
                referenceType: 'shop_listing',
                referenceId: $listing->id,
                description: sprintf('Purchase: %s', $item->name),
            ));

            if ($listing->stock !== null) {
                $listing->decrement('stock');
            }

            return $this->addItemToInventory->handle(new AddItemToInventoryDTO(
                characterId: $character->id,
                itemId: $item->id,
                tenantId: $listing->tenant_id,
                acquiredVia: AcquisitionMethod::Purchase,
            ));
        });
    }
}
