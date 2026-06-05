<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Actions;

use He4rt\Activity\Voice\DTOs\NewVoiceMessageDTO;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class NewVoiceMessage
{
    public function __construct(
        private IncrementExperience $incrementExperience,
    ) {}

    public function persist(NewVoiceMessageDTO $voiceDTO): void
    {
        try {
            DB::transaction(function () use ($voiceDTO): void {
                $userDto = ResolveUserProviderDTO::make([
                    'tenant_id' => $voiceDTO->tenantId,
                    'provider' => $voiceDTO->provider,
                    'external_account_id' => $voiceDTO->externalAccountId,
                    'model_type' => (new User)->getMorphClass(),
                    'username' => $voiceDTO->username,
                ]);

                $userContext = resolve(ResolveUserContext::class)->handle($userDto);

                $obtainedExperience = $this->incrementExperience->incrementByVoiceMessage(
                    $userContext->character->id,
                    $voiceDTO->voiceState,
                );

                Voice::query()->create([
                    'tenant_id' => $voiceDTO->tenantId,
                    'external_identity_id' => $userContext->provider->id,
                    'channel_name' => $voiceDTO->channelName,
                    'channel_id' => $voiceDTO->channelId,
                    'state' => $voiceDTO->voiceState->value,
                    'obtained_experience' => $obtainedExperience,
                    'occurred_at' => now()->utc(),
                ]);
            });
        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('NewVoiceMessage failed', [
                'external_account_id' => $voiceDTO->externalAccountId,
                'tenant_id' => $voiceDTO->tenantId,
                'exception' => $throwable,
            ]);
        }
    }
}
