import DiscordGlyph from '../DiscordGlyph';
import { VoiceDockAvatar } from './VoiceDockAvatar';
import type { VoiceRoster } from './VoiceRoster';

export type DockLayout = 'row' | 'col';

export interface VoiceDockProps {
    roster: VoiceRoster | null;
    layout?: DockLayout;
}

export function VoiceDock({ roster, layout = 'col' }: VoiceDockProps) {
    if (!roster || roster.members.length === 0) return null;

    const isRow = layout === 'row';

    return (
        <div
            className="absolute flex flex-col items-center"
            style={{
                right: 12,
                top: 12,
                width: isRow ? 'auto' : 92,
                padding: isRow ? '13px 16px 16px' : '13px 0 16px',
                background: 'linear-gradient(180deg,#1d0f31 0%,#160b26 100%)',
                border: '1px solid rgba(139,47,232,.32)',
                borderRadius: 20,
                boxShadow: '0 16px 44px rgba(0,0,0,.5), inset 0 0 0 1px rgba(201,164,255,.05)',
                animation: 'sbIn .5s cubic-bezier(.18,.9,.3,1.1) both',
            }}
        >
            <div className="flex flex-col items-center gap-[5px]">
                <DiscordGlyph />
                <span
                    className="font-saira-cond font-extrabold"
                    style={{ fontSize: 12, letterSpacing: '.12em', color: '#c9a4ff' }}
                >
                    {roster.members.length} NA SALA
                </span>
            </div>

            <div
                style={{
                    width: 52,
                    height: 1,
                    background: 'rgba(139,47,232,.3)',
                    margin: '11px 0 15px',
                }}
            />

            <div className={`flex items-center ${isRow ? 'flex-row' : 'flex-col'}`} style={{ gap: 15 }}>
                {roster.members.map((m) => (
                    <VoiceDockAvatar key={m.userId} member={m} />
                ))}
            </div>
        </div>
    );
}

export default VoiceDock;
