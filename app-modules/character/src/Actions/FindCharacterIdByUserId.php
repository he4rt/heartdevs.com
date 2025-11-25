<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Character\Entities\CharacterEntity;
use He4rt\Character\Models\Character;

class FindCharacterIdByUserId
{
    public function handle(string $userId): string
    {
        return $this->findCharacterByUserId($userId);
    }

    private function findCharacterByUserId(string $userId): string
    {
        $character = Character::query()
            ->where('tenant_id', request()->tenant_id)
            ->where('user_id', $userId)->firstOrFail();

        return CharacterEntity::make(
            $character->toArray()
        )->id;
    }
}
