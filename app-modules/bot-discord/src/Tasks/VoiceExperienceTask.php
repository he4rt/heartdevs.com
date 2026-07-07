<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Tasks;

use Discord\Parts\WebSockets\VoiceStateUpdate;
use He4rt\Activity\Voice\Actions\NewVoiceMessage;
use He4rt\Activity\Voice\DTOs\NewVoiceMessageDTO;
use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Laracord\Tasks\Task;
use Throwable;

class VoiceExperienceTask extends Task
{
    /**
     * Determine if the task handler should execute during boot.
     */
    protected bool $eager = false;

    /**
     * The task interval in seconds.
     *
     * Env-overridable so the cadence can be lowered locally for testing
     * without affecting production (defaults to 20 minutes).
     */
    public function getInterval(): int
    {
        return config()->integer('he4rt.discord.voice_xp_interval');
    }

    /**
     * Handle the task.
     */
    public function handle(): void
    {
        $guildId = config('he4rt.discord.guild_id');
        $guild = $this->discord()->guilds->get('id', $guildId);

        if ($guild === null) {
            return;
        }

        $tenantId = (string) config('he4rt.tenant_id');

        $afkChannelId = $guild->afk_channel_id;

        foreach ($guild->voice_states as $voiceState) {
            /** @var VoiceStateUpdate $voiceState */
            try {
                $this->processVoiceState($voiceState, $tenantId, $afkChannelId);
            } catch (Throwable $e) {
                $this->logger()->error(sprintf(
                    'VoiceExperienceTask failed for user %s: %s | File: %s | Line: %s',
                    $voiceState->user_id,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                ));
            }
        }
    }

    private function processVoiceState(
        VoiceStateUpdate $voiceState,
        string $tenantId,
        ?string $afkChannelId,
    ): void {
        if ($voiceState->channel_id === null) {
            return;
        }

        if ($afkChannelId !== null && (string) $voiceState->channel_id === $afkChannelId) {
            return;
        }

        if ($this->isBot($voiceState)) {
            return;
        }

        $state = $this->resolveVoiceState($voiceState);

        $channelId = (string) $voiceState->channel_id;

        $dto = new NewVoiceMessageDTO(
            tenantId: $tenantId,
            provider: IdentityProvider::Discord,
            externalAccountId: (string) $voiceState->user_id,
            voiceState: $state,
            channelName: $voiceState->channel->name ?? $channelId,
            channelId: $channelId,
            username: $voiceState->member?->user->username,
        );

        resolve(NewVoiceMessage::class)->persist($dto);
    }

    private function resolveVoiceState(VoiceStateUpdate $voiceState): VoiceStatesEnum
    {
        if ($voiceState->deaf || $voiceState->self_deaf) {
            return VoiceStatesEnum::Disabled;
        }

        if ($voiceState->mute || $voiceState->self_mute) {
            return VoiceStatesEnum::Muted;
        }

        return VoiceStatesEnum::Unmuted;
    }

    private function isBot(VoiceStateUpdate $voiceState): bool
    {
        $user = $voiceState->member?->user ?? $voiceState->user;

        if ($user === null) {
            return true;
        }

        return (bool) $user->bot;
    }
}
