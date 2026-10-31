import type { FragmentDto } from './feed';

export type OverlayScene = 'coworking' | 'starting' | 'voice' | 'chat' | 'pub';

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

export type PubSettings = {
    slido_event_hash: string | null;
};

export type SceneSettings = Record<string, string | number | null>;

export interface SlidoReplyPayload {
    id: number;
    text: string;
    authorName: string | null;
    isFromModerator: boolean;
}

export interface SlidoQuestionPayload {
    id: number;
    text: string;
    authorName: string | null;
    score: number;
    isHighlighted: boolean;
    isAnswered: boolean;
    isVisible: boolean;
    createdAt: string;
    replies: SlidoReplyPayload[];
}

export type SlidoQuestionChanges = Partial<Pick<SlidoQuestionPayload, 'score' | 'isHighlighted' | 'isAnswered'>>;

export interface SlidoEventPayload {
    hash: string;
    name: string;
    code: string;
    isActive: boolean;
    participantUrl: string;
}

export interface PubSnapshot {
    event: SlidoEventPayload;
    questions: SlidoQuestionPayload[];
    qrSvg: string;
}

export interface ChatBadgePayload {
    setId: string;
    version: string;
    url: string | null;
}

export interface ChatMessagePayload {
    sourceId?: string | null;
    msgId: string;
    chatterId: string;
    username: string;
    color: string | null;
    badges: ChatBadgePayload[];
    fragments: FragmentDto[];
}

export interface SessionPayload {
    sourceId?: string;
    title: string | null;
    category: string | null;
    startedAt: string;
}

export interface OverlayChannelSource {
    channel: string;
    authEndpoint: string;
    scene?: string;
    sources?: string[] | null;
}

export interface OverlayPageProps<TSettings extends object = SceneSettings> extends OverlayChannelSource {
    scene: OverlayScene;
    handle: string;
    settings: TSettings;
    recentChat: ChatMessagePayload[];
    session: SessionPayload | null;
}

export interface PubPageProps extends OverlayPageProps<PubSettings> {
    pub: PubSnapshot | null;
}
