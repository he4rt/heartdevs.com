import { useCallback, useState } from 'react';
import type { ChatMessageDto } from '../feed';

const MAX = 8;

export interface ChatMessages {
    messages: ChatMessageDto[];
    push(dto: ChatMessageDto): void;
    remove(msgId: string): void;
}

export function useChatMessages(initial: ChatMessageDto[] = []): ChatMessages {
    const [messages, setMessages] = useState<ChatMessageDto[]>(() => initial.slice(-MAX));

    const push = useCallback((dto: ChatMessageDto) => {
        setMessages((prev) => [...prev, dto].slice(-MAX));
    }, []);

    const remove = useCallback((msgId: string) => {
        setMessages((prev) => prev.filter((m) => m.msgId !== msgId));
    }, []);

    return { messages, push, remove };
}
