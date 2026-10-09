export interface VoiceMember {
    userId: string;
    displayName: string;
    avatarUrl: string | null;
    speaking: boolean;
    selfMute: boolean;
    selfDeaf: boolean;
    serverMute: boolean;
    serverDeaf: boolean;
}

export interface VoiceMemberCardProps {
    member: VoiceMember;
}

export function VoiceMemberCard({ member }: VoiceMemberCardProps) {
    const muted = member.selfMute || member.serverMute;
    const deafened = member.selfDeaf || member.serverDeaf;
    const initial = member.displayName.charAt(0).toUpperCase() || '?';

    const ring = member.speaking
        ? 'ring-2 ring-spotify shadow-[0_0_0_4px_rgba(30,215,96,.25),0_6px_18px_rgba(0,0,0,.45)]'
        : 'ring-1 ring-brand/[.30] shadow-[0_6px_16px_rgba(0,0,0,.35)]';

    return (
        <div className="flex w-[78px] flex-col items-center gap-[6px]">
            <div className="relative">
                <div
                    className={`relative flex h-[64px] w-[64px] items-center justify-center overflow-hidden rounded-full bg-[linear-gradient(145deg,var(--color-disc),#120a1f)] ${ring}`}
                >
                    {member.avatarUrl ? (
                        <img src={member.avatarUrl} alt={member.displayName} className="h-full w-full object-cover" />
                    ) : (
                        <span className="text-[26px] font-extrabold text-brand-light">{initial}</span>
                    )}
                </div>

                {(muted || deafened) && (
                    <div className="absolute -right-[2px] -bottom-[2px] flex h-[24px] w-[24px] items-center justify-center rounded-full bg-ink-900 ring-2 ring-ink-950">
                        <span className="text-[14px] leading-none">{deafened ? '🔇' : '🎙️'}</span>
                    </div>
                )}
            </div>

            <span className="max-w-full truncate text-[13px] font-bold leading-none text-brand-pale">
                {member.displayName}
            </span>
        </div>
    );
}

export default VoiceMemberCard;
