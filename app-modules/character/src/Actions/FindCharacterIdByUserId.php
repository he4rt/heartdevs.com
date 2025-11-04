<?php

declare(strict_types=1);

namespace He4rt\Character\Actions;

use He4rt\Character\Entities\CharacterEntity;
use He4rt\Character\Models\Character;
use He4rt\Shared\TTL;
use Illuminate\Support\Facades\Cache;

class FindCharacterIdByUserId
{
    public function handle(string $userId): string
    {
        $cacheCharacterKey = sprintf('user-%s-character-id', $userId);

        return Cache::remember(
            $cacheCharacterKey,
            TTL::fromDays(2),
            fn () => $this->findCharacterByUserId($userId)
        );
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
