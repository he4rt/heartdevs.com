import { useCallback, useEffect, useLayoutEffect, useRef } from 'react';
import type { ChatMessageDto } from '../../feed';
import { clipToFit, type ChatConfig, type ChatStyle } from '../../lib/chatOverlay';
import ChatLine from './ChatLine';

export interface ChatColumnProps {
    config: ChatConfig;
    messages: ChatMessageDto[];
    onExpire(msgId: string): void;
}

const SHELL_CLASSES: Record<ChatStyle, string> = {
    lines: '',
    bubbles: '',
    panel: 'rounded-[0.9em] bg-[rgba(18,8,31,0.74)] px-[0.8em] py-[0.6em] shadow-[0_0.4em_1.2em_rgba(0,0,0,0.35)] ring-1 ring-white/10',
    spotlight: '',
    terminal: 'rounded-[0.5em] bg-[rgba(13,9,20,0.88)] shadow-[0_0.4em_1.2em_rgba(0,0,0,0.4)] ring-1 ring-white/10',
    glass: '',
};

const LIST_GAP: Record<ChatStyle, string> = {
    lines: 'gap-[0.2em]',
    bubbles: 'gap-[0.4em]',
    panel: 'gap-[0.25em]',
    spotlight: 'gap-[0.55em]',
    terminal: 'gap-[0.1em] px-[0.8em] py-[0.5em]',
    glass: 'gap-[0.35em]',
};

const HAS_CHROME: ReadonlySet<ChatStyle> = new Set(['panel', 'terminal']);

function TerminalBar() {
    return (
        <div className="flex shrink-0 items-center gap-[0.35em] border-b border-white/10 px-[0.7em] py-[0.4em] font-terminal text-[0.65em] text-white/50">
            <span className="size-[0.8em] rounded-full bg-[#ff5f57]" />
            <span className="size-[0.8em] rounded-full bg-[#febc2e]" />
            <span className="size-[0.8em] rounded-full bg-[#28c840]" />
            <span className="ml-[0.6em]">~/he4rt/chat</span>
        </div>
    );
}

export function ChatColumn({ config, messages, onExpire }: ChatColumnProps) {
    const frameRef = useRef<HTMLDivElement>(null);
    const shellRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLDivElement>(null);

    const clip = useCallback(() => {
        const frame = frameRef.current;
        const shell = shellRef.current;
        const list = listRef.current;

        if (frame && shell && list) clipToFit(frame, shell, list);
    }, []);

    useLayoutEffect(clip, [clip, messages, config]);

    useEffect(() => {
        const frame = frameRef.current;

        if (!frame) return;

        const observer = new ResizeObserver(clip);
        observer.observe(frame);
        void document.fonts.ready.then(clip);

        return () => observer.disconnect();
    }, [clip]);

    const isRightAligned = config.alignment === 'right';
    const isNewestOnTop = config.direction === 'newest_top';
    const hidesEmptyShell = HAS_CHROME.has(config.style) && messages.length === 0;

    return (
        <div className={`flex h-full w-full ${isRightAligned ? 'justify-end' : 'justify-start'}`}>
            <div
                ref={frameRef}
                className={`flex h-full max-w-full flex-col ${isNewestOnTop ? 'justify-start' : 'justify-end'}`}
                style={{ width: config.width, fontSize: config.fontSize }}
            >
                <div
                    ref={shellRef}
                    className={`flex max-h-full min-h-0 flex-col overflow-hidden ${SHELL_CLASSES[config.style]} ${hidesEmptyShell ? 'invisible' : ''}`}
                >
                    {config.style === 'terminal' && <TerminalBar />}
                    <div
                        ref={listRef}
                        className={`flex min-h-0 overflow-hidden ${isNewestOnTop ? 'flex-col-reverse' : 'flex-col'} ${isRightAligned ? 'items-end text-right' : 'items-start text-left'} ${LIST_GAP[config.style]}`}
                    >
                        {messages.map((message, index) => (
                            <ChatLine
                                key={message.msgId}
                                message={message}
                                style={config.style}
                                fadeAfterSeconds={config.fadeAfterSeconds}
                                isNewest={index === messages.length - 1}
                                onExpire={onExpire}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ChatColumn;
