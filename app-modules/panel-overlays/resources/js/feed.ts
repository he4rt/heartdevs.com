export interface TextFragmentDto {
    kind: 'text';
    text: string;
}

export interface EmoteFragmentDto {
    kind: 'emote';
    id: string;
    url: string;
}

export type FragmentDto = TextFragmentDto | EmoteFragmentDto;

export interface ChatBadgeDto {
    setId: string;
    version: string;
    url: string;
}

export interface ChatMessageDto {
    kind: 'chatMessage';
    msgId: string;
    username: string;
    color: string;
    channel: string;
    badges: ChatBadgeDto[];
    fragments: FragmentDto[];
}

export interface ChatMessageDeletedDto {
    kind: 'chatMessageDeleted';
    msgId: string;
}

export type SubTierDto = 'tier1' | 'tier2' | 'tier3' | 'prime';

export interface FollowEventDto {
    kind: 'streamEvent';
    type: 'follow';
    username: string;
}

export interface SubEventDto {
    kind: 'streamEvent';
    type: 'sub';
    username: string;
    tier: SubTierDto;
    months: number;
}

export interface DonationEventDto {
    kind: 'streamEvent';
    type: 'donation';
    username: string;
    amountCents: number;
    message: string;
}

export interface GiftSubEventDto {
    kind: 'streamEvent';
    type: 'giftSub';
    username: string;
    tier: SubTierDto;
    total: number;
}

export interface CheerEventDto {
    kind: 'streamEvent';
    type: 'cheer';
    username: string;
    bits: number;
    message: string;
}

export interface RaidEventDto {
    kind: 'streamEvent';
    type: 'raid';
    fromChannel: string;
    viewers: number;
}

export interface ViewerCountUpdateDto {
    kind: 'streamEvent';
    type: 'viewerCountUpdate';
    count: number;
}

export type StreamEventDto =
    | FollowEventDto
    | SubEventDto
    | DonationEventDto
    | GiftSubEventDto
    | CheerEventDto
    | RaidEventDto
    | ViewerCountUpdateDto;

export interface NowPlayingDto {
    kind: 'nowPlaying';
    title: string;
    artist: string;
    album: string;
    artUrl: string | null;
    status: 'playing' | 'paused' | 'stopped';
}

export interface VoiceMemberDto {
    userId: string;
    displayName: string;
    avatarUrl: string | null;
    speaking: boolean;
    selfMute: boolean;
    selfDeaf: boolean;
    serverMute: boolean;
    serverDeaf: boolean;
}

export interface VoiceRosterDto {
    kind: 'voiceRoster';
    channelId: string | null;
    channelName: string | null;
    members: VoiceMemberDto[];
}

export interface OverlayValuesDto {
    values: Record<string, string>;
    since: number;
}

export interface OverlayConfigDto {
    kind: 'overlayConfig';
    overlays: Record<string, OverlayValuesDto>;
}

export interface LevelProgressDto {
    kind: 'levelProgress';
    level: number;
    xp: number;
    xpToNext: number;
}

export type FeedEventDto =
    | ChatMessageDto
    | ChatMessageDeletedDto
    | StreamEventDto
    | NowPlayingDto
    | VoiceRosterDto
    | OverlayConfigDto
    | LevelProgressDto;
