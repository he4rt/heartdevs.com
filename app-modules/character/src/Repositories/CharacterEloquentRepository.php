<?php

declare(strict_types=1);

namespace He4rt\Character\Repositories;

use He4rt\Character\Contracts\CharacterRepository;
use He4rt\Character\Entities\CharacterEntity;
use He4rt\Character\Models\Character;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CharacterEloquentRepository implements CharacterRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Character::query()->paginate($perPage);
    }

    public function findById(string $characterId): CharacterEntity
    {
        return CharacterEntity::make(
            Character::query()->find($characterId)->toArray()
        );
    }

    public function claimDailyBonus(CharacterEntity $character)
    {
        return Character::query()->find($character->id)
            ->update(['daily_bonus_claimed_at' => now()]);
    }

    public function updateReputation(CharacterEntity $character)
    {
        return Character::query()->find($character->id)
            ->update(['reputation' => $character->reputation->getPoints()]);
    }

    public function findByUserId(string $userId): CharacterEntity
    {
        return CharacterEntity::make(
            Character::query()->where('user_id', $userId)->first()->toArray()
        );
    }

    public function updateExperience(CharacterEntity $character)
    {
        return Character::query()
            ->find($character->id)
            ->update(['experience' => $character->level->getExperience()]);
    }

    public function claimBadge(string $characterId, int $badgeId): void
    {
        Character::query()->find($characterId)->badges()->attach($badgeId, [
            'claimed_at' => now(),
            'tenant_id' => request()->input('tenant_id'),
        ]);
    }
}
