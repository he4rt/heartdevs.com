export interface EventAlertProps {
    icon: string;
    accent: string;
    title: string;
    name: string;
    detail: string;
}

export function EventAlert({ icon, accent, title, name, detail }: EventAlertProps) {
    return (
        <div className="absolute right-0 bottom-0 left-[756px] h-[122px] overflow-hidden">
            <div
                className="absolute inset-0 flex items-center bg-[linear-gradient(90deg,#1a0b2e_0%,#241036_60%,#1a0b2e_100%)] pl-[26px]"
                style={{
                    borderTop: `3px solid ${accent}`,
                    animation: 'evIn 0.5s cubic-bezier(.18,.9,.3,1.15) both',
                }}
            >
                <div
                    className="absolute top-0 left-0 h-full w-[120px] skew-x-[-18deg] bg-[linear-gradient(90deg,transparent,rgba(255,255,255,.16),transparent)]"
                    style={{ animation: 'evShine 2.6s ease-in-out infinite' }}
                />

                <div
                    className="flex h-[74px] w-[74px] flex-none items-center justify-center rounded-[18px] bg-white/6 text-[38px]"
                    style={{
                        boxShadow: `inset 0 0 0 2px ${accent}`,
                        animation: 'evIcon 0.6s ease both',
                    }}
                >
                    {icon}
                </div>

                <div className="ml-[22px] flex flex-col">
                    <span
                        className="font-saira-cond text-[24px] font-extrabold uppercase tracking-[.12em]"
                        style={{ color: accent }}
                    >
                        {title}
                    </span>
                    <span className="text-[30px] font-extrabold italic leading-[1.05] text-white">{name}</span>
                </div>

                <span
                    className="mr-[34px] ml-auto font-saira-cond text-[46px] font-extrabold text-white"
                    style={{ textShadow: `0 0 22px ${accent}` }}
                >
                    {detail}
                </span>
            </div>
        </div>
    );
}

export default EventAlert;
