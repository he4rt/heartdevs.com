import { VoiceDockAvatar } from './VoiceDockAvatar';
import type { VoiceRoster } from './VoiceRoster';

const MAX_AVATARS = 8;

const DiscordGlyph = () => (
    <svg width="26" height="26" viewBox="0 0 24 24" fill="#8b2fe8">
        <path d="M20 4.4A17 17 0 0 0 15.7 3l-.2.4a13 13 0 0 1 3.7 1.2 12.7 12.7 0 0 0-11 0A12.6 12.6 0 0 1 12 3.4L11.8 3A17 17 0 0 0 7.5 4.4 18 18 0 0 0 4 17a17 17 0 0 0 5.2 2.6l.6-1a11 11 0 0 1-1.8-.9l.4-.3a12 12 0 0 0 10.2 0l.4.3a11 11 0 0 1-1.8.9l.6 1A17 17 0 0 0 23.5 17 18 18 0 0 0 20 4.4ZM9.3 14.7c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.7 1.7-1.5 1.7Zm5.4 0c-.8 0-1.5-.8-1.5-1.7s.7-1.7 1.5-1.7 1.5.8 1.5 1.7-.7 1.7-1.5 1.7Z" />
    </svg>
);

export interface VoiceColumnProps {
    roster: VoiceRoster | null;
    present: boolean;
}

export function VoiceColumn({ roster, present }: VoiceColumnProps) {
    const members = roster?.members ?? [];
    const shown = members.slice(0, MAX_AVATARS);
    const overflow = members.length - shown.length;

    return (
        <div
            className="absolute flex flex-col items-center"
            style={{
                top: 12,
                bottom: 130,
                right: 0,
                width: 92,
                padding: '16px 0 18px',
                background: 'linear-gradient(180deg,#1d0f31 0%,#160b26 100%)',
                border: '1px solid rgba(139,47,232,.32)',
                borderRight: 'none',
                borderRadius: '20px 0 0 20px',
                boxShadow: '0 16px 44px rgba(0,0,0,.5), inset 0 0 0 1px rgba(201,164,255,.05)',
                transform: present ? 'translateX(0)' : 'translateX(100%)',
                opacity: present ? 1 : 0,
                transition: 'transform .48s cubic-bezier(.22,.9,.32,1), opacity .48s ease',
            }}
        >
            <div className="flex flex-col items-center gap-[5px]">
                <DiscordGlyph />
                <span
                    className="font-saira-cond font-extrabold"
                    style={{ fontSize: 12, letterSpacing: '.12em', color: '#c9a4ff' }}
                >
                    {members.length} NA SALA
                </span>
            </div>

            <div
                style={{
                    width: 52,
                    height: 1,
                    background: 'rgba(139,47,232,.3)',
                    margin: '13px 0 0',
                }}
            />

            <div className="flex flex-1 flex-col items-center justify-center" style={{ gap: 15 }}>
                {shown.map((m) => (
                    <VoiceDockAvatar key={m.userId} member={m} />
                ))}
                {overflow > 0 && (
                    <span className="font-saira-cond font-extrabold" style={{ fontSize: 13, color: '#c9a4ff' }}>
                        +{overflow}
                    </span>
                )}
            </div>
        </div>
    );
}

export default VoiceColumn;
