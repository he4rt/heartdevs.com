import VoiceMemberCard, { type VoiceMember } from './VoiceMemberCard';

export interface VoiceRoster {
    channelId: string;
    channelName: string | null;
    members: VoiceMember[];
}

export interface VoiceRosterProps {
    roster: VoiceRoster | null;
}

export function VoiceRoster({ roster }: VoiceRosterProps) {
    if (!roster) return null;

    return (
        <div className="flex flex-col gap-[10px] rounded-[18px] border border-brand/[.22] bg-ink-900/80 px-[18px] py-[14px] [box-shadow:0_8px_24px_rgba(0,0,0,.45),inset_0_0_0_1px_rgba(201,164,255,.12)] backdrop-blur-sm">
            <div className="flex items-center gap-[8px]">
                <span className="text-[15px] leading-none">🎧</span>
                <span className="font-saira-cond text-[13px] font-bold tracking-[.16em] text-brand-light">
                    {roster.channelName?.toUpperCase() ?? 'VOICE'}
                </span>
            </div>

            {roster.members.length === 0 ? (
                <span className="text-[13px] font-medium text-brand-faint">waiting for the crew…</span>
            ) : (
                <div className="flex flex-wrap gap-x-[14px] gap-y-[12px]">
                    {roster.members.map((m) => (
                        <VoiceMemberCard key={m.userId} member={m} />
                    ))}
                </div>
            )}
        </div>
    );
}

export default VoiceRoster;
