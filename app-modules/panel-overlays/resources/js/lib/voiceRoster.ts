import type { VoiceRosterDto } from '../feed';
import type { VoiceRoster } from '../ui/voice/VoiceRoster';

export function toVoiceRoster(dto: VoiceRosterDto): VoiceRoster | null {
    if (dto.channelId === null) return null;

    return {
        channelId: dto.channelId,
        channelName: dto.channelName,
        members: dto.members.map((m) => ({
            userId: m.userId,
            displayName: m.displayName,
            avatarUrl: m.avatarUrl,
            speaking: m.speaking,
            selfMute: m.selfMute,
            selfDeaf: m.selfDeaf,
            serverMute: m.serverMute,
            serverDeaf: m.serverDeaf,
        })),
    };
}
