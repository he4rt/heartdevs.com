<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Actions;

use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class RecordVoicePresence
{
    public function persist(RecordVoicePresenceDTO $dto): void
    {
        try {
            DB::transaction(static function () use ($dto): void {
                $userDto = ResolveUserProviderDTO::make([
                    'tenant_id' => $dto->tenantId,
                    'provider' => $dto->provider,
                    'external_account_id' => $dto->externalAccountId,
                    'model_type' => (new User)->getMorphClass(),
                    'username' => $dto->username,
                ]);

                $userContext = resolve(ResolveUserContext::class)->handle($userDto);

                Voice::query()->create([
                    'tenant_id' => $dto->tenantId,
                    'external_identity_id' => $userContext->provider->id,
                    'channel_name' => $dto->channelName,
                    'channel_id' => $dto->channelId,
                    'state' => $dto->presence->value,
                    'obtained_experience' => 0,
                    'occurred_at' => now()->utc(),
                ]);
            });
        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('RecordVoicePresence failed', [
                'external_account_id' => $dto->externalAccountId,
                'tenant_id' => $dto->tenantId,
                'exception' => $throwable,
            ]);
        }
    }
}
