<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions;

use He4rt\BotDiscord\DTO\ResolvedUserCharacter;
use He4rt\Character\Models\Character;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\User\Models\User;
use Ramsey\Uuid\Uuid;

class UserCharacterResolver
{
    public function resolve(
        ProviderEnum $provider,
        string $providerId,
        string $username,
        int $tenantId,
    ): ResolvedUserCharacter {
        $providerEntity = Provider::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (! $providerEntity) {
            $user = User::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'username' => $username,
                'name' => $username,
                'is_donator' => false,
            ]);

            $user->address()->create();
            $user->information()->create();

            $character = $user->character()->create([
                'tenant_id' => $tenantId,
            ]);

            $providerEntity = $user->providers()->create([
                'tenant_id' => $tenantId,
                'model_type' => User::class,
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);

            return new ResolvedUserCharacter(
                user: $user,
                provider: $providerEntity,
                character: $character,
                isNewUser: true,
                isNewCharacter: true,
            );
        }

        $user = $providerEntity->model;

        $character = Character::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return new ResolvedUserCharacter(
            user: $user,
            provider: $providerEntity,
            character: $character,
            isNewUser: false,
            isNewCharacter: false,
        );
    }
}
