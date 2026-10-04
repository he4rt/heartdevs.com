import { useCallback, useState } from 'react';
import type { ChatMessageDto } from '../feed';

const DEFAULT_MAX = 8;

export interface ChatMessages {
    messages: ChatMessageDto[];
    push(dto: ChatMessageDto): void;
    remove(msgId: string): void;
    removeChatter(chatterId: string): void;
    expire(msgId: string): void;
    clear(): void;
}

export function useChatMessages(initial: ChatMessageDto[] = [], max = DEFAULT_MAX): ChatMessages {
    const [messages, setMessages] = useState<ChatMessageDto[]>(() => initial.slice(-max));

    const push = useCallback(
        (dto: ChatMessageDto) => {
            setMessages((prev) => [...prev, dto].slice(-max));
        },
        [max],
    );

    const remove = useCallback((msgId: string) => {
        setMessages((prev) => prev.filter((m) => m.msgId !== msgId));
    }, []);

    const removeChatter = useCallback((chatterId: string) => {
        setMessages((prev) => prev.filter((m) => m.chatterId !== chatterId));
    }, []);

    const expire = useCallback((msgId: string) => {
        setMessages((prev) => prev.slice(prev.findIndex((m) => m.msgId === msgId) + 1));
    }, []);

    const clear = useCallback(() => setMessages([]), []);

    return { messages, push, remove, removeChatter, expire, clear };
}
