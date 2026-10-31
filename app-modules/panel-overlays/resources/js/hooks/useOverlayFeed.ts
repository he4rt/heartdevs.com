import { useEffect, useRef } from 'react';
import type {
    ChatMessageDto,
    FeedEventDto,
    LevelProgressDto,
    NowPlayingDto,
    OverlayConfigDto,
    PubQuestionDto,
    PubQuestionPatchDto,
    PubQuestionsDto,
    StreamEventDto,
    VoiceRosterDto,
} from '../feed';
import { subscribeDemoFeed } from '../lib/demoFeed';
import { subscribeOverlayChannel } from '../lib/overlayChannel';
import type { OverlayChannelSource } from '../types';

export interface OverlayFeedHandlers {
    onChatMessage?(dto: ChatMessageDto): void;
    onChatDeleted?(msgId: string): void;
    onChatterCleared?(chatterId: string): void;
    onChatCleared?(): void;
    onStreamEvent?(dto: StreamEventDto): void;
    onNowPlaying?(dto: NowPlayingDto): void;
    onVoiceRoster?(dto: VoiceRosterDto): void;
    onOverlayConfig?(dto: OverlayConfigDto): void;
    onLevelProgress?(dto: LevelProgressDto): void;
    onPubQuestions?(dto: PubQuestionsDto): void;
    onPubQuestion?(dto: PubQuestionDto): void;
    onPubQuestionPatch?(dto: PubQuestionPatchDto): void;
    onChannelReconnected?(): void;
}

export function useOverlayFeed(
    { channel, authEndpoint, scene, sources }: OverlayChannelSource,
    handlers: OverlayFeedHandlers,
): void {
    const handlersRef = useRef(handlers);
    handlersRef.current = handlers;
    const sourcesKey = sources?.join(',') ?? '';

    useEffect(() => {
        const listener = (dto: FeedEventDto) => dispatch(dto, handlersRef.current);
        const isDemo = new URLSearchParams(window.location.search).has('demo');
        const sceneSources = sourcesKey === '' ? null : sourcesKey.split(',');

        return isDemo
            ? subscribeDemoFeed(listener)
            : subscribeOverlayChannel({ channel, authEndpoint, scene, sources: sceneSources }, listener);
    }, [channel, authEndpoint, scene, sourcesKey]);
}

function dispatch(dto: FeedEventDto, handlers: OverlayFeedHandlers): void {
    switch (dto.kind) {
        case 'chatMessage':
            handlers.onChatMessage?.(dto);
            break;
        case 'chatMessageDeleted':
            handlers.onChatDeleted?.(dto.msgId);
            break;
        case 'chatterCleared':
            handlers.onChatterCleared?.(dto.chatterId);
            break;
        case 'chatCleared':
            handlers.onChatCleared?.();
            break;
        case 'streamEvent':
            handlers.onStreamEvent?.(dto);
            break;
        case 'nowPlaying':
            handlers.onNowPlaying?.(dto);
            break;
        case 'voiceRoster':
            handlers.onVoiceRoster?.(dto);
            break;
        case 'overlayConfig':
            handlers.onOverlayConfig?.(dto);
            break;
        case 'levelProgress':
            handlers.onLevelProgress?.(dto);
            break;
        case 'pubQuestions':
            handlers.onPubQuestions?.(dto);
            break;
        case 'pubQuestion':
            handlers.onPubQuestion?.(dto);
            break;
        case 'pubQuestionPatch':
            handlers.onPubQuestionPatch?.(dto);
            break;
        case 'channelReconnected':
            handlers.onChannelReconnected?.();
            break;
    }
}
