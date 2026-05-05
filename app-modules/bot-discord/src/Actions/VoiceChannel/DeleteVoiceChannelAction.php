<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Actions\VoiceChannel;

use Discord\Discord;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use stdClass;

final class DeleteVoiceChannelAction
{
    public function execute(Discord $discord): void
    {
        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        $validChannels = [];
        $hasInvalid = false;

        foreach ($channels as $channel) {
            $dto = $this->normalizeChannel($channel);

            if (!$dto instanceof VoiceChannelDTO) {
                $hasInvalid = true;

                continue;
            }

            $validChannels[] = $dto;

            if ($dto->isEmpty() && $dto->isLongTermEmpty()) {
                $this->delete($dto->guildId, $dto->channelId, $discord);
            }
        }

        if ($hasInvalid) {
            cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $this->dtosToArrays($validChannels));
        }
    }

    /**
     * @param  array<VoiceChannelDTO>  $dtos
     * @return array<array<string, mixed>>
     */
    private function dtosToArrays(array $dtos): array
    {
        return array_map(fn (VoiceChannelDTO $dto) => [
            'guildId' => $dto->guildId,
            'channelId' => $dto->channelId,
            'ownerId' => $dto->ownerId,
            'usersCount' => $dto->usersCount,
            'users' => $dto->users,
            'lastJoinedAt' => $dto->lastJoinedAt?->toIso8601String(),
        ], $dtos);
    }

    private function normalizeChannel(mixed $channel): ?VoiceChannelDTO
    {
        if ($channel instanceof VoiceChannelDTO) {
            return $channel;
        }

        $data = null;

        if (is_array($channel)) {
            $data = $channel;
        } elseif ($channel instanceof stdClass) {
            $data = (array) $channel;
        }

        if ($data !== null && isset($data['guildId'], $data['channelId'], $data['ownerId'])) {
            return VoiceChannelDTO::make($data);
        }

        return null;
    }

    private function delete(string $guildId, string $channelId, Discord $discord): void
    {
        $guild = $discord->guilds->get('id', $guildId);

        if ($guild && $guild->channels->has($channelId)) {
            $guild->channels->delete($channelId);
        }

        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        $filtered = array_values(array_filter($channels, function ($channel) use ($channelId): bool {
            $dto = $this->normalizeChannel($channel);

            return !$dto instanceof VoiceChannelDTO || $dto->channelId !== $channelId;
        }));

        $filteredArrays = array_map(function ($channel): ?array {
            $dto = $this->normalizeChannel($channel);

            if (!$dto instanceof VoiceChannelDTO) {
                return null;
            }

            return [
                'guildId' => $dto->guildId,
                'channelId' => $dto->channelId,
                'ownerId' => $dto->ownerId,
                'usersCount' => $dto->usersCount,
                'users' => $dto->users,
                'lastJoinedAt' => $dto->lastJoinedAt?->toIso8601String(),
            ];
        }, $filtered);
        $filteredArrays = array_filter($filteredArrays);
        $filteredArrays = array_values($filteredArrays);

        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $filteredArrays);
    }
}
