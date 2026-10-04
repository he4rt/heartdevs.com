import { useEffect, useRef } from 'react';
import type {
    ChatMessageDto,
    FeedEventDto,
    LevelProgressDto,
    NowPlayingDto,
    OverlayConfigDto,
    StreamEventDto,
    VoiceRosterDto,
} from '../feed';
import { subscribeDemoFeed } from '../lib/demoFeed';

export interface OverlayFeedHandlers {
    onChatMessage?(dto: ChatMessageDto): void;
    onChatDeleted?(msgId: string): void;
    onStreamEvent?(dto: StreamEventDto): void;
    onNowPlaying?(dto: NowPlayingDto): void;
    onVoiceRoster?(dto: VoiceRosterDto): void;
    onOverlayConfig?(dto: OverlayConfigDto): void;
    onLevelProgress?(dto: LevelProgressDto): void;
}

export function useOverlayFeed(handlers: OverlayFeedHandlers): void {
    const handlersRef = useRef(handlers);
    handlersRef.current = handlers;

    useEffect(() => subscribeDemoFeed((dto) => dispatch(dto, handlersRef.current)), []);
}

function dispatch(dto: FeedEventDto, handlers: OverlayFeedHandlers): void {
    switch (dto.kind) {
        case 'chatMessage':
            handlers.onChatMessage?.(dto);
            break;
        case 'chatMessageDeleted':
            handlers.onChatDeleted?.(dto.msgId);
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
    }
}
