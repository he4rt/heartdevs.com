import He4rtLogo from './He4rtLogo';

export interface TopBarProps {
    channel: string;
}

export default function TopBar({ channel }: TopBarProps) {
    return (
        <>
            <div className="absolute left-0 top-0 flex h-[100px] w-[712px] items-center border-b border-brand/25 bg-[linear-gradient(180deg,var(--color-ink-800)_0%,var(--color-ink-900)_100%)]">
                <div className="relative flex h-[100px] w-[100px] flex-none items-center justify-center bg-[linear-gradient(150deg,var(--color-brand-bright)_0%,var(--color-brand-deep)_100%)] animate-[logoPulse_3.4s_ease-in-out_infinite]">
                    <He4rtLogo tone="light" className="h-[74px] w-[74px]" />
                    <div className="absolute left-0 bottom-[-12px] h-0 w-0 border-l-[12px] border-l-yellow border-b-[12px] border-b-transparent" />
                </div>

                <div className="flex items-center gap-[18px] pl-[28px] text-brand-light">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22 5.8c-.7.3-1.5.5-2.3.6.8-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1A4.1 4.1 0 0 0 11.8 9c0 .3 0 .6.1.9A11.6 11.6 0 0 1 3.4 4.6a4.1 4.1 0 0 0 1.3 5.5c-.7 0-1.3-.2-1.8-.5v.1c0 2 1.4 3.6 3.3 4-.4.1-.8.2-1.1.2-.3 0-.6 0-.8-.1.5 1.6 2 2.8 3.8 2.8a8.2 8.2 0 0 1-6.1 1.7 11.6 11.6 0 0 0 17.9-9.8v-.5c.8-.6 1.5-1.3 2-2.1Z" />
                    </svg>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <rect x="3" y="3" width="18" height="18" rx="5" />
                        <circle cx="12" cy="12" r="4" />
                        <circle cx="17.5" cy="6.5" r="1.3" fill="currentColor" stroke="none" />
                    </svg>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.7c-2.8.6-3.4-1.3-3.4-1.3-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.6 2.4 1.1 3 .8.1-.6.3-1.1.6-1.4-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.6 0 0 .8-.3 2.7 1a9.3 9.3 0 0 1 4.9 0c1.9-1.3 2.7-1 2.7-1 .5 1.3.2 2.3.1 2.6.6.7 1 1.6 1 2.7 0 3.9-2.4 4.7-4.6 5 .4.3.7.9.7 1.9v2.8c0 .3.2.6.7.5A10 10 0 0 0 12 2Z" />
                    </svg>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.9 4.8 12 4.8 12 4.8s-5.9 0-7.6.4a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.7.4 7.6.4 7.6.4s5.9 0 7.6-.4a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8ZM10 15V9l5.2 3-5.2 3Z" />
                    </svg>
                    <span className="ml-[4px] font-saira-semi text-[30px] font-extrabold tracking-[.01em] text-brand-light">
                        /{channel}
                    </span>
                </div>
            </div>

            <div className="absolute left-[712px] top-0 h-0 w-0 border-t-[90px] border-t-yellow border-r-[96px] border-r-transparent" />
        </>
    );
}
