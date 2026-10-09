import type { ChatMessageDto } from '../feed';
import type { ChatBubbleProps } from '../ui/chat/ChatBubble';

export function toChatBubble(dto: ChatMessageDto): ChatBubbleProps {
    return {
        name: dto.username,
        badges: dto.badges.map((b) => ({ url: b.url, label: b.setId })),
        parts: dto.fragments.map((f) =>
            f.kind === 'text' ? { kind: 'text', text: f.text } : { kind: 'emote', url: f.url },
        ),
    };
}
