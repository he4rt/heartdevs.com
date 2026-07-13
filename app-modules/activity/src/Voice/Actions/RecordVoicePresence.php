<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\Actions;

use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RecordVoicePresence
{
    /**
     * Persist every transition of a single voice-state change atomically.
     *
     * A channel move emits both a `left` and a `joined`; they must commit
     * together or not at all, otherwise the presence log is left with an
     * unpaired row. The transaction rolls back and rethrows on failure — error
     * logging is the caller's concern (the gateway event boundary).
     *
     * @param  list<RecordVoicePresenceDTO>  $dtos
     */
    public function persistMany(array $dtos): void
    {
        if ($dtos === []) {
            return;
        }

        DB::transaction(function () use ($dtos): void {
            foreach ($dtos as $dto) {
                $this->record($dto);
            }
        });
    }

    private function record(RecordVoicePresenceDTO $dto): void
    {
        $userDto = ResolveUserProviderDTO::make([
            'provider' => $dto->provider,
            'external_account_id' => $dto->externalAccountId,
            'model_type' => (new User)->getMorphClass(),
            'username' => $dto->username,
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        Voice::query()->create([
            'external_identity_id' => $userContext->provider->id,
            'channel_name' => $dto->channelName,
            'channel_id' => $dto->channelId,
            'state' => $dto->presence->value,
            'obtained_experience' => 0,
            'occurred_at' => now()->utc(),
        ]);
    }
}
