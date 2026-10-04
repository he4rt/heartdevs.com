import DiscordGlyph from '../DiscordGlyph';

export interface DiscordCalloutProps {
    invite?: string;
}

export function DiscordCallout({ invite = 'discord.gg/he4rt' }: DiscordCalloutProps) {
    return (
        <div className="absolute left-[800px] right-[24px] bottom-[150px] flex h-[124px] items-center overflow-hidden rounded-[22px] border border-brand/35 bg-[linear-gradient(90deg,var(--color-ink-800)_0%,var(--color-panel)_55%,var(--color-ink-800)_100%)] pr-[22px] shadow-[0_18px_50px_rgba(0,0,0,.45),inset_0_0_0_1px_rgba(201,164,255,.06)]">
            <div
                className="pointer-events-none absolute top-0 left-0 h-full w-[140px] skew-x-[-18deg] bg-[linear-gradient(90deg,transparent,rgba(255,255,255,.10),transparent)]"
                style={{ animation: 'evShine 4.5s ease-in-out infinite' }}
            />

            <div className="relative flex h-full w-[124px] flex-none items-center justify-center bg-[linear-gradient(150deg,var(--color-brand-bright)_0%,var(--color-brand-deep)_100%)] animate-[logoPulse_3.4s_ease-in-out_infinite]">
                <DiscordGlyph size={62} fill="#ffffff" />
                <div className="absolute left-0 bottom-0 h-0 w-0 border-l-[14px] border-l-yellow border-t-[14px] border-t-transparent" />
            </div>

            <div className="ml-[26px] flex min-w-0 flex-col">
                <span className="font-saira-cond text-[18px] font-bold tracking-[.28em] text-yellow">
                    PRESENÇA DO AULÃO
                </span>
                <span className="font-saira text-[26px] font-bold leading-[1.15] text-white">
                    A live é aqui na Twitch, mas a <span className="text-brand-light">presença</span> é no Discord da
                    He4rt.
                </span>
                <span className="font-saira text-[18px] font-medium text-brand-muted">
                    Entra na call pra ser contado 💜
                </span>
            </div>

            <div className="ml-auto flex flex-none items-center gap-[12px] rounded-full bg-yellow px-[26px] py-[12px] shadow-[0_0_28px_rgba(255,203,5,.45)]">
                <DiscordGlyph size={28} fill="#15101f" />
                <span className="font-saira-semi text-[34px] font-extrabold italic tracking-[-.01em] text-[#15101f]">
                    {invite}
                </span>
            </div>
        </div>
    );
}

export default DiscordCallout;
