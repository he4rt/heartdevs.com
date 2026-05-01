<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordProfileDTO;
use Illuminate\Support\Facades\Date;
use Ramsey\Uuid\Uuid;

final class ImportDiscordProfileAction
{
    public function handle(DiscordProfileDTO $dto, int $tenantId): ExternalIdentity
    {
        $user = User::query()->where('username', $dto->username)->first();

        if (!$user instanceof User) {
            $name = User::query()->where('name', $dto->name)->exists()
                ? $dto->username
                : $dto->name;

            $user = User::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'username' => $dto->username,
                'name' => $name,
                'is_donator' => false,
            ]);
        }

        $user->tenants()->syncWithoutDetaching([$tenantId]);

        $discordIdentity = ExternalIdentity::query()->updateOrCreate(
            [
                'provider' => IdentityProvider::Discord,
                'external_account_id' => $dto->discordId,
                'tenant_id' => $tenantId,
            ],
            [
                'model_type' => (new User)->getMorphClass(),
                'model_id' => $user->id,
                'type' => IdentityProvider::Discord->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => ClientAccessManager::make(),
                'connected_at' => $dto->joinedAt ? Date::parse($dto->joinedAt) : null,
                'metadata' => $dto->metadata,
            ],
        );

        foreach ($dto->connectedAccounts as $account) {
            ExternalIdentity::query()->updateOrCreate(
                [
                    'provider' => $account->provider,
                    'external_account_id' => $account->externalAccountId,
                    'tenant_id' => $tenantId,
                ],
                [
                    'model_type' => (new User)->getMorphClass(),
                    'model_id' => $user->id,
                    'type' => $account->provider->getType(),
                    'credentials_type' => CredentialsType::OAuth2,
                    'credentials' => ClientAccessManager::make(),
                    'connected_at' => $dto->joinedAt ? Date::parse($dto->joinedAt) : null,
                    'metadata' => $account->metadata,
                ]
            );
        }

        return $discordIdentity;
    }
}
