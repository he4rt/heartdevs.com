import { useCallback, useState } from 'react';
import type { ChatMessageDto } from '../feed';

const MAX = 8;

export interface ChatMessages {
    messages: ChatMessageDto[];
    push(dto: ChatMessageDto): void;
    remove(msgId: string): void;
    removeChatter(chatterId: string): void;
    clear(): void;
}

export function useChatMessages(initial: ChatMessageDto[] = []): ChatMessages {
    const [messages, setMessages] = useState<ChatMessageDto[]>(() => initial.slice(-MAX));

    const push = useCallback((dto: ChatMessageDto) => {
        setMessages((prev) => [...prev, dto].slice(-MAX));
    }, []);

    const remove = useCallback((msgId: string) => {
        setMessages((prev) => prev.filter((m) => m.msgId !== msgId));
    }, []);

    const removeChatter = useCallback((chatterId: string) => {
        setMessages((prev) => prev.filter((m) => m.chatterId !== chatterId));
    }, []);

    const clear = useCallback(() => setMessages([]), []);

    return { messages, push, remove, removeChatter, clear };
}
