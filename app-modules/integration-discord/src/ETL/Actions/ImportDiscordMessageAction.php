<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Actions;

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordMessageDTO;
use Ramsey\Uuid\Uuid;

final class ImportDiscordMessageAction
{
    public function handle(DiscordMessageDTO $dto, int $tenantId): Message
    {
        $identity = $this->resolveAuthorIdentity($dto, $tenantId);

        return Message::query()->updateOrCreate(
            ['provider_message_id' => $dto->discordMessageId],
            $dto->toDatabase([
                'tenant_id' => $tenantId,
                'external_identity_id' => $identity->id,
                'obtained_experience' => 0,
            ]),
        );
    }

    private function resolveAuthorIdentity(DiscordMessageDTO $dto, int $tenantId): ExternalIdentity
    {
        $identity = ExternalIdentity::query()
            ->where('provider', IdentityProvider::Discord)
            ->where('external_account_id', $dto->authorDiscordId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($identity) {
            return $identity;
        }

        $user = User::query()->firstOrCreate(
            ['username' => $dto->authorUsername],
            [
                'id' => Uuid::uuid4()->toString(),
                'name' => $this->uniqueName($dto),
                'is_donator' => false,
            ]
        );

        $user->tenants()->syncWithoutDetaching([$tenantId]);

        return ExternalIdentity::query()->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $dto->authorDiscordId,
            'tenant_id' => $tenantId,
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'type' => IdentityProvider::Discord->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(),
            'metadata' => ['author' => [
                'id' => $dto->authorDiscordId,
                'username' => $dto->authorUsername,
                'global_name' => $dto->authorName,
                'bot' => $dto->isBot,
            ]],
        ]);
    }

    private function uniqueName(DiscordMessageDTO $dto): string
    {
        foreach ([$dto->authorName, $dto->authorUsername, $dto->authorUsername.'-'.$dto->authorDiscordId] as $candidate) {
            if (!User::query()->where('name', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $dto->authorDiscordId;
    }
}
