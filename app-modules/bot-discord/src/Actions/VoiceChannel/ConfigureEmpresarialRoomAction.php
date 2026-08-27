<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use He4rt\BotDiscord\DTO\EmpresarialOverwritePlan;
use He4rt\BotDiscord\DTO\EmpresarialRoomDecision;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use He4rt\BotDiscord\Enums\EmpresarialRejectionReason;

/**
 * Decides whether the caller may turn the voice room they are in into a Sala Empresarial
 * for the selected company, and — if so — what permission overwrites to stamp.
 *
 * Pure decision: no Discord I/O. The command edge applies the returned plan.
 */
final class ConfigureEmpresarialRoomAction
{
    /**
     * @param  list<string>  $callerRoleIds  Discord role ids held by the caller.
     * @param  list<VoiceChannelDTO>  $activeChannels  The `/sala`-tracked rooms cache.
     */
    public function execute(
        string $companySlug,
        array $callerRoleIds,
        ?string $currentChannelId,
        array $activeChannels,
    ): EmpresarialRoomDecision {
        if (!$this->isInsideTrackedRoom($currentChannelId, $activeChannels)) {
            return EmpresarialRoomDecision::reject(EmpresarialRejectionReason::NotInTrackedRoom);
        }

        $partnerRoleId = $this->resolvePartnerRoleId($companySlug);

        if ($partnerRoleId === null) {
            return EmpresarialRoomDecision::reject(EmpresarialRejectionReason::UnknownCompany);
        }

        if (!in_array($partnerRoleId, $callerRoleIds, strict: true)) {
            return EmpresarialRoomDecision::reject(EmpresarialRejectionReason::MissingPartnerRole);
        }

        return EmpresarialRoomDecision::approve(
            EmpresarialOverwritePlan::for($companySlug, $partnerRoleId),
        );
    }

    /**
     * @param  list<VoiceChannelDTO>  $activeChannels
     */
    private function isInsideTrackedRoom(?string $currentChannelId, array $activeChannels): bool
    {
        if ($currentChannelId === null) {
            return false;
        }

        return array_any($activeChannels, fn (VoiceChannelDTO $channel): bool => $channel->channelId === $currentChannelId);
    }

    private function resolvePartnerRoleId(string $companySlug): ?string
    {
        /** @var array<string, string> $partners */
        $partners = config('bot-discord.roles.partners', []);

        if (!array_key_exists($companySlug, $partners)) {
            return null;
        }

        return $partners[$companySlug];
    }
}
