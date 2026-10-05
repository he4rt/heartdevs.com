import { useState } from 'react';
import type { OverlayConfigDto } from '../../feed';
import { useChatMessages } from '../../hooks/useChatMessages';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { chatConfigFrom, type ChatConfig } from '../../lib/chatOverlay';
import { toChatMessage } from '../../lib/overlayEvents';
import type { ChatSettings, OverlayPageProps } from '../../types';
import ChatColumn from '../../ui/chat/ChatColumn';

const OVERLAY_SLUG = 'chat';
const MAX_MESSAGES = 50;

function chatConfigOf(dto: OverlayConfigDto): ChatConfig | null {
    const values = dto.overlays[OVERLAY_SLUG]?.values;

    return values ? chatConfigFrom(values) : null;
}

export default function ChatOverlay({ channel, authEndpoint, settings, recentChat }: OverlayPageProps<ChatSettings>) {
    const [config, setConfig] = useState(() => chatConfigFrom(settings));
    const chat = useChatMessages(recentChat.map(toChatMessage), MAX_MESSAGES);

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onChatMessage: chat.push,
            onChatDeleted: chat.remove,
            onChatterCleared: chat.removeChatter,
            onChatCleared: chat.clear,
            onOverlayConfig: (dto) => setConfig((current) => chatConfigOf(dto) ?? current),
        },
    );

    return <ChatColumn config={config} messages={chat.messages} onExpire={chat.expire} />;
}
