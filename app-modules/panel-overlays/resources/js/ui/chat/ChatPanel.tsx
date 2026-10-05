import type { ReactNode } from 'react';
import He4rtLogo from '../He4rtLogo';

export interface ChatPanelProps {
    title: string;
    children: ReactNode;
}

export function ChatPanel({ title, children }: ChatPanelProps) {
    return (
        <div className="absolute left-0 top-[100px] bottom-[122px] w-[710px] overflow-hidden border-t-2 border-brand/35 bg-[linear-gradient(180deg,var(--color-ink-900)_0%,var(--color-ink-950)_100%)]">
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center overflow-hidden opacity-[.55]">
                <He4rtLogo tone="brand" className="w-[520px]" style={{ transform: 'translateY(-4%)' }} />
            </div>

            <div className="absolute left-4 top-4 z-[2] flex items-center gap-2 rounded-full border border-brand-light/[.28] bg-[rgba(20,10,31,.55)] px-[14px] py-[7px]">
                <span className="h-2 w-2 rounded-full bg-brand shadow-[0_0_10px_var(--color-brand)]" />
                <span className="font-saira-cond text-[14px] font-bold tracking-[.14em] text-brand-pale">{title}</span>
            </div>

            <div className="absolute inset-x-0 top-[54px] bottom-0 flex flex-col justify-end gap-6 overflow-hidden pt-4 pb-[22px]">
                {children}
            </div>
        </div>
    );
}

export default ChatPanel;
