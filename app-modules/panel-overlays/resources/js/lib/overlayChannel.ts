import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { FeedEventDto } from '../feed';
import type { ChatMessagePayload, OverlayChannelSource } from '../types';
import {
    toChatMessage,
    toOverlayConfig,
    toStreamEvent,
    type AlertPayload,
    type SettingsPayload,
} from './overlayEvents';

type Listener = (dto: FeedEventDto) => void;

export function subscribeOverlayChannel(
    { channel, authEndpoint }: OverlayChannelSource,
    listener: Listener,
): () => void {
    const port = Number(import.meta.env.VITE_REVERB_PORT);

    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint,
        Pusher,
    });

    echo.private(channel)
        .listen('.alert.triggered', (alert: AlertPayload) => listener(toStreamEvent(alert)))
        .listen('.chat.message', (message: ChatMessagePayload) => listener(toChatMessage(message)))
        .listen('.chat.message-deleted', ({ msgId }: { msgId: string }) =>
            listener({ kind: 'chatMessageDeleted', msgId }),
        )
        .listen('.chat.chatter-cleared', ({ chatterId }: { chatterId: string }) =>
            listener({ kind: 'chatterCleared', chatterId }),
        )
        .listen('.chat.cleared', () => listener({ kind: 'chatCleared' }))
        .listen('.settings.updated', ({ scene, settings }: SettingsPayload) =>
            listener(toOverlayConfig(scene, settings, Date.now())),
        );

    return () => echo.disconnect();
}
