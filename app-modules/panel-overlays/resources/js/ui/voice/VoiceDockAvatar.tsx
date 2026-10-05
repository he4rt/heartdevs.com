import type { VoiceMember } from './VoiceMemberCard';

const SIZE = 56;

const GRADIENTS = [
    'linear-gradient(150deg,#9a3cff,#6d1fd0)',
    'linear-gradient(150deg,#ff6fae,#c43d86)',
    'linear-gradient(150deg,#1ed760,#0f9b46)',
    'linear-gradient(150deg,#ffcb05,#d99e00)',
];

function gradientFor(userId: string): string {
    let hash = 0;
    for (let i = 0; i < userId.length; i++) {
        hash = (hash * 31 + userId.charCodeAt(i)) | 0;
    }
    return GRADIENTS[Math.abs(hash) % GRADIENTS.length];
}

export function VoiceDockAvatar({ member }: { member: VoiceMember }) {
    const muted = member.selfMute || member.serverMute;
    const deafened = member.selfDeaf || member.serverDeaf;
    const initial = member.displayName.charAt(0).toUpperCase() || '?';

    const dotColor = member.speaking ? '#1ed760' : muted || deafened ? '#ed4245' : '#3a2b52';

    return (
        <div className="relative" style={{ width: SIZE, height: SIZE }}>
            {member.speaking && (
                <div
                    className="absolute rounded-full"
                    style={{
                        inset: -4,
                        border: '3px solid #1ed760',
                        animation: 'speakRing 1.4s ease-in-out infinite',
                    }}
                />
            )}

            <div
                className="flex items-center justify-center overflow-hidden rounded-full font-extrabold text-white"
                style={{
                    width: SIZE,
                    height: SIZE,
                    fontSize: 24,
                    background: member.avatarUrl ? '#160b26' : gradientFor(member.userId),
                    boxShadow: '0 0 0 2px rgba(201,164,255,.16)',
                }}
            >
                {member.avatarUrl ? (
                    <img src={member.avatarUrl} alt={member.displayName} className="h-full w-full object-cover" />
                ) : (
                    initial
                )}
            </div>

            <div
                className="absolute rounded-full"
                style={{
                    right: -1,
                    bottom: -1,
                    width: 18,
                    height: 18,
                    background: dotColor,
                    border: '3px solid #160b26',
                }}
            />
        </div>
    );
}

export default VoiceDockAvatar;
