import { useEffect } from 'react';
import { useChatMessages } from '../../hooks/useChatMessages';
import { useFooterBar } from '../../hooks/useFooterBar';
import { useLevelProgress } from '../../hooks/useLevelProgress';
import { useNowPlaying } from '../../hooks/useNowPlaying';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { useVoiceRoster } from '../../hooks/useVoiceRoster';
import { useVoicePresence } from '../../hooks/useVoicePresence';
import { toChatBubble } from '../../lib/chat';
import { toEventAlert } from '../../lib/eventAlert';
import { toChatMessage } from '../../lib/overlayEvents';
import { Stage } from '../../ui/Stage';
import TopBar from '../../ui/TopBar';
import ChatPanel from '../../ui/chat/ChatPanel';
import ChatBubble from '../../ui/chat/ChatBubble';
import FooterBar from '../../ui/footer/FooterBar';
import NowPlaying from '../../ui/footer/NowPlaying';
import LevelBar from '../../ui/footer/LevelBar';
import EventAlert from '../../ui/footer/EventAlert';
import VoiceColumn from '../../ui/voice/VoiceColumn';
import type { OverlayPageProps } from '../../types';

const ALERT_DURATION_MS = 6000;

export default function CoworkingOverlay({ handle, channel, authEndpoint, recentChat }: OverlayPageProps) {
    const chat = useChatMessages(recentChat.map(toChatMessage));
    const footer = useFooterBar();
    const leveling = useLevelProgress();
    const np = useNowPlaying();
    const voice = useVoiceRoster();
    const presence = useVoicePresence(voice.roster);

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onStreamEvent: footer.pushEvent,
            onNowPlaying: np.onDto,
            onVoiceRoster: voice.onDto,
            onLevelProgress: leveling.onDto,
        },
    );

    const currentEvent = footer.state.current;
    useEffect(() => {
        if (!currentEvent) return;
        const id = setTimeout(footer.endAlert, ALERT_DURATION_MS);
        return () => clearTimeout(id);
    }, [currentEvent, footer.endAlert]);

    const alert = footer.state.mode === 'alert' && currentEvent ? toEventAlert(currentEvent) : null;

    return (
        <Stage>
            <TopBar channel={handle} />

            <ChatPanel title="CHAT AO VIVO">
                {chat.messages.map((m) => (
                    <ChatBubble key={m.msgId} {...toChatBubble(m)} />
                ))}
            </ChatPanel>

            <VoiceColumn present={presence.present} roster={presence.roster} />

            <FooterBar>
                <NowPlaying track={np.track} />
                {alert ? <EventAlert {...alert} /> : <LevelBar {...leveling.progress} />}
            </FooterBar>
        </Stage>
    );
}
