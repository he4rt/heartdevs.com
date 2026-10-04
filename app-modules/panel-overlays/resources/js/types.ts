import type { FragmentDto } from './feed';

export type OverlayScene = 'coworking' | 'starting' | 'voice' | 'chat';

export type StartingSoonSettings = {
    title: string | null;
    starts_at: string | null;
};

export type VoiceSettings = {
    layout: 'col' | 'row';
};

export type ChatSettings = {
    style: string;
    fade_after_seconds: number;
    font_size: number;
    width: number;
    direction: string;
    alignment: string;
};

export type SceneSettings = Record<string, string | number | null>;

export interface ChatBadgePayload {
    setId: string;
    version: string;
    url: string | null;
}

export interface ChatMessagePayload {
    msgId: string;
    chatterId: string;
    username: string;
    color: string | null;
    badges: ChatBadgePayload[];
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
