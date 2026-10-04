export interface NowPlayingTrack {
    title: string;
    artist: string;
    artUrl: string | null;
    isPlaying: boolean;
}

export interface NowPlayingProps {
    track: NowPlayingTrack | null;
}

const EQ_DELAYS = ['0s', '0.25s', '0.5s', '0.15s'];

export function NowPlaying({ track }: NowPlayingProps) {
    const title = track?.title ?? 'lofi beats to code to';
    const artist = track?.artist ?? 'He4rt Radio';
    const artUrl = track?.artUrl ?? null;
    const isPlaying = track ? track.isPlaying : true;
    const spin = isPlaying ? 'spin 6s linear infinite' : 'none';

    return (
        <div className="absolute bottom-[14px] left-[14px] flex h-[94px] items-center">
            <div className="relative flex h-[94px] w-[94px] flex-none items-center justify-center overflow-hidden rounded-[18px] bg-[linear-gradient(145deg,var(--color-disc),#120a1f)] [box-shadow:0_8px_24px_rgba(0,0,0,.45),inset_0_0_0_1px_rgba(201,164,255,.18)]">
                <div
                    className={
                        artUrl
                            ? 'absolute inset-0 rounded-[18px] bg-[conic-gradient(from_0deg,var(--color-brand),#3a1f5c,var(--color-brand))]'
                            : 'flex h-[42px] w-[42px] items-center justify-center rounded-full bg-[conic-gradient(from_0deg,var(--color-brand),#3a1f5c,var(--color-brand))]'
                    }
                    style={{ animation: spin }}
                >
                    {!artUrl && <div className="h-[14px] w-[14px] rounded-full bg-ink-900" />}
                </div>
                {artUrl && (
                    <img
                        src={artUrl}
                        alt={`${title} — ${artist}`}
                        className="absolute inset-[6px] h-[82px] w-[82px] rounded-[14px] object-cover"
                    />
                )}
                <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="var(--color-spotify)"
                    className="absolute top-[7px] left-[7px]"
                >
                    <circle cx="12" cy="12" r="11" fill="var(--color-spotify)" />
                    <path
                        d="M7 9.5c3-.8 6.5-.5 9 1M7.5 12.2c2.5-.6 5.3-.4 7.3 1M8 14.8c2-.5 4-.3 5.6.8"
                        stroke="#0a0a0a"
                        strokeWidth="1.3"
                        strokeLinecap="round"
                        fill="none"
                    />
                </svg>
            </div>

            <div className="ml-[12px] flex h-[26px] items-end gap-[3px]">
                {EQ_DELAYS.map((delay, i) => (
                    <span
                        key={i}
                        className="w-[4px] rounded-[2px] bg-spotify"
                        style={{
                            animation: isPlaying ? `barEq 0.9s ease-in-out infinite ${delay}` : 'none',
                        }}
                    />
                ))}
            </div>

            <div className="ml-[14px] flex flex-col justify-center">
                <span className="font-saira-cond text-[13px] font-bold tracking-[.16em] text-spotify">NOW PLAYING</span>
                <span className="text-[18px] font-bold leading-[1.15] text-white">{title}</span>
                <span className="text-[14px] font-medium leading-[1.15] text-brand-faint">{artist}</span>
            </div>
        </div>
    );
}

export default NowPlaying;
