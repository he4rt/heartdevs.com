import type { ChatBadgeDto, FragmentDto } from './feed';

export type OverlayScene = 'coworking' | 'starting' | 'voice';

export type StartingSoonSettings = {
    title: string | null;
    starts_at: string | null;
};

export type VoiceSettings = {
    layout: 'col' | 'row';
};

export type SceneSettings = Record<string, string | null>;

export interface ChatMessagePayload {
    msgId: string;
    username: string;
    color: string | null;
    badges: ChatBadgeDto[];
    fragments: FragmentDto[];
}

export interface SessionPayload {
    title: string | null;
    category: string | null;
    startedAt: string;
}

export interface OverlayChannelSource {
    channel: string;
    authEndpoint: string;
}

export interface OverlayPageProps<TSettings extends object = SceneSettings> extends OverlayChannelSource {
    scene: OverlayScene;
    handle: string;
    settings: TSettings;
    recentChat: ChatMessagePayload[];
    session: SessionPayload | null;
}
