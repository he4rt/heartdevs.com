import { useChatMessages } from '../../hooks/useChatMessages';
import { useCountdown } from '../../hooks/useCountdown';
import { useLevelProgress } from '../../hooks/useLevelProgress';
import { useNowPlaying } from '../../hooks/useNowPlaying';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { useStartingSoonConfig } from '../../hooks/useStartingSoonConfig';
import { toChatBubble } from '../../lib/chat';
import { toChatMessage, toOverlayConfig } from '../../lib/overlayEvents';
import { Stage } from '../../ui/Stage';
import TopBar from '../../ui/TopBar';
import ChatPanel from '../../ui/chat/ChatPanel';
import ChatBubble from '../../ui/chat/ChatBubble';
import FooterBar from '../../ui/footer/FooterBar';
import NowPlaying from '../../ui/footer/NowPlaying';
import LevelBar from '../../ui/footer/LevelBar';
import DiscordCallout from '../../ui/starting/DiscordCallout';
import StartingBackdrop from '../../ui/starting/StartingBackdrop';
import StartingHero from '../../ui/starting/StartingHero';
import type { OverlayPageProps, StartingSoonSettings } from '../../types';

export default function StartingSoonOverlay({
    scene,
    handle,
    channel,
    authEndpoint,
    settings,
    recentChat,
}: OverlayPageProps<StartingSoonSettings>) {
    const { config, onDto: onOverlayConfig } = useStartingSoonConfig(toOverlayConfig(scene, settings, Date.now()));
    const { remainingMs, done } = useCountdown(config.deadline);
    const chat = useChatMessages(recentChat.map(toChatMessage));
    const np = useNowPlaying();
    const leveling = useLevelProgress();

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onNowPlaying: np.onDto,
            onOverlayConfig,
            onLevelProgress: leveling.onDto,
        },
    );

    return (
        <Stage>
            <StartingBackdrop done={done} />
            <TopBar channel={handle} />

            <ChatPanel title="CHAT AO VIVO">
                {chat.messages.map((m) => (
                    <ChatBubble key={m.msgId} {...toChatBubble(m)} />
                ))}
            </ChatPanel>

            <StartingHero title={config.title} remainingMs={remainingMs} totalMs={config.totalMs} done={done} />
            <DiscordCallout invite="discord.gg/he4rt" />

            <FooterBar>
                <NowPlaying track={np.track} />
                <LevelBar {...leveling.progress} />
            </FooterBar>
        </Stage>
    );
}
