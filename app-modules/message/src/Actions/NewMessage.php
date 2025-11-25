<?php

declare(strict_types=1);

namespace He4rt\Message\Actions;

use Exception;
use He4rt\Character\Entities\CharacterEntity;
use He4rt\Character\Models\Character;
use He4rt\Message\DTO\NewMessageDTO;
use He4rt\Provider\Models\Provider;
use He4rt\User\Models\User;
use Ramsey\Uuid\Uuid;

final readonly class NewMessage
{
    public function __construct(
        private PersistMessage $persistMessage,

    ) {}

    public function persist(NewMessageDTO $messageDTO): void
    {

        try {

            $providerEntity = Provider::query()
                ->where('model_type', User::class)
                ->where('provider', $messageDTO->provider)
                ->where('provider_id', $messageDTO->providerId)
                ->firstOrFail();

        } catch (Exception) {
            $user = User::query()
                ->create([
                    'id' => Uuid::uuid4()->toString(),
                    'username' => $messageDTO->providerUsername,
                    'name' => $messageDTO->providerUsername,
                    'is_donator' => false,
                ]);

            $user->address()->create();
            $user->information()->create();
            $user->character()->create([
                'tenant_id' => $messageDTO->tenantId,
            ]);

            $providerEntity = $user->providers()->create([
                'tenant_id' => $messageDTO->tenantId,
                'model_type' => User::class,
                'provider' => $messageDTO->provider,
                'provider_id' => $messageDTO->providerId,
            ]);
        }

        $character = Character::query()
            ->where('tenant_id', $messageDTO->tenantId)
            ->where('user_id', $providerEntity->model_id)
            ->first();

        $characterEntity = CharacterEntity::make($character->toArray());
        $obtainedExperience = $characterEntity->level->generateExperience($messageDTO->content);

        $character->update(['experience' => $characterEntity->level->getExperience()]);

        $this->persistMessage->handle(
            $messageDTO,
            $obtainedExperience,
            $providerEntity->id,
        );
    }
}
