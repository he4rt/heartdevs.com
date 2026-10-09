import type { AnimationEvent, CSSProperties } from 'react';
import type { ChatMessageDto } from '../../feed';
import { readableNameColor, type ChatStyle } from '../../lib/chatOverlay';

export interface ChatLineProps {
    message: ChatMessageDto;
    style: ChatStyle;
    fadeAfterSeconds: number;
    isNewest: boolean;
    onExpire(msgId: string): void;
}

const ENTER_ANIMATION = 'chatLineIn 0.28s cubic-bezier(.2,.8,.2,1) both';
const EXIT_ANIMATION_MS = 450;

function Badges({ message }: { message: ChatMessageDto }) {
    return message.badges.map((badge) => (
        <img
            key={`${badge.setId}/${badge.version}`}
            src={badge.url}
            alt={badge.setId}
            className="mr-[0.2em] inline-block h-[1em] w-[1em] rounded-[0.2em] align-[-0.14em]"
        />
    ));
}

function Fragments({ message }: { message: ChatMessageDto }) {
    return message.fragments.map((fragment, i) =>
        fragment.kind === 'emote' ? (
            <img
                key={i}
                src={fragment.url}
                alt=""
                className="mx-[0.05em] inline-block h-[1.35em] w-[1.35em] object-contain align-middle"
            />
        ) : (
            <span key={i}>{fragment.text}</span>
        ),
    );
}

function Cursor() {
    return (
        <span className="ml-[0.2em] inline-block h-[1em] w-[0.55em] translate-y-[0.15em] animate-[cursorBlink_1s_steps(1)_infinite] bg-[#e8e3f0]" />
    );
}

function lineBody(style: ChatStyle, message: ChatMessageDto, nameColor: string, isNewest: boolean) {
    const name = (
        <span className="font-extrabold" style={{ color: nameColor }}>
            {message.username}
        </span>
    );

    switch (style) {
        case 'lines':
            return (
                <div className="chat-outline font-saira leading-[1.3] font-semibold text-white">
                    <Badges message={message} />
                    {name}
                    <span>: </span>
                    <Fragments message={message} />
                </div>
            );
        case 'bubbles':
            return (
                <div className="w-fit max-w-full rounded-[0.8em] bg-[rgba(22,11,38,0.84)] px-[0.75em] py-[0.45em] font-saira leading-[1.3] shadow-[0_0.3em_0.8em_rgba(0,0,0,0.35)] ring-1 ring-brand/35">
                    <div className="text-[0.8em]">
                        <Badges message={message} />
                        {name}
                    </div>
                    <div className="font-medium break-words text-brand-pale">
                        <Fragments message={message} />
                    </div>
                </div>
            );
        case 'panel':
            return (
                <div className="font-saira leading-[1.35] font-medium break-words text-brand-pale">
                    <Badges message={message} />
                    {name}
                    <span className="text-brand-faint">: </span>
                    <Fragments message={message} />
                </div>
            );
        case 'spotlight':
            return (
                <div className="chat-outline font-saira-cond leading-[1.1]">
                    <div className="text-[0.8em] tracking-[0.04em] uppercase">
                        <Badges message={message} />
                        {name}
                    </div>
                    <div className="text-[1.25em] font-extrabold break-words text-white">
                        <Fragments message={message} />
                    </div>
                </div>
            );
        case 'terminal':
            return (
                <div className="font-terminal text-[0.85em] leading-[1.45] break-words text-[#e8e3f0]">
                    <span style={{ color: nameColor }}>{message.username}</span>
                    <span className="text-white/45">@he4rt</span>
                    <span className="text-[#7dd3fc]">:~$ </span>
                    <Fragments message={message} />
                    {isNewest && <Cursor />}
                </div>
            );
        case 'glass':
            return (
                <div className="chat-soft-shadow w-fit max-w-full rounded-[0.7em] bg-[rgba(255,255,255,0.1)] px-[0.7em] py-[0.35em] font-saira leading-[1.3] font-medium break-words text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.35),0_0.3em_0.8em_rgba(0,0,0,0.25)] ring-1 ring-white/25">
                    <Badges message={message} />
                    {name} <Fragments message={message} />
                </div>
            );
    }
}

export function ChatLine({ message, style, fadeAfterSeconds, isNewest, onExpire }: ChatLineProps) {
    const fades = fadeAfterSeconds > 0;
    const animation: CSSProperties = {
        animation: fades
            ? `${ENTER_ANIMATION}, chatLineOut ${EXIT_ANIMATION_MS}ms ease-in ${fadeAfterSeconds}s forwards`
            : ENTER_ANIMATION,
    };

    const handleAnimationEnd = (event: AnimationEvent<HTMLDivElement>) => {
        if (event.animationName === 'chatLineOut') onExpire(message.msgId);
    };

    return (
        <div className="max-w-full shrink-0" style={animation} onAnimationEnd={handleAnimationEnd}>
            {lineBody(style, message, readableNameColor(message.color, message.username), isNewest)}
        </div>
    );
}

export default ChatLine;
