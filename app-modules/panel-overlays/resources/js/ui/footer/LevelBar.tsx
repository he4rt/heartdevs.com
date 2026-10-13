import { useEffect, useRef, useState } from 'react';

export interface LevelBarProps {
    level: number;
    xp: number;
    xpToNext: number;
}

const LEVEL_UP_HOLD_MS = 2600;
const FILL_TRANSITION_MS = 700;

const formatXp = (n: number) => n.toLocaleString('pt-BR');

export function LevelBar({ level, xp, xpToNext }: LevelBarProps) {
    const prevLevel = useRef(level);
    const [celebrating, setCelebrating] = useState(false);

    useEffect(() => {
        const leveledUp = level > prevLevel.current;
        prevLevel.current = level;
        if (!leveledUp) return;
        setCelebrating(true);
        const hold = setTimeout(() => setCelebrating(false), LEVEL_UP_HOLD_MS);
        return () => clearTimeout(hold);
    }, [level]);

    const fillPercent = Math.min(100, (xp / Math.max(1, xpToNext)) * 100);

    return (
        <div className="absolute right-0 bottom-0 left-[792px] flex h-[122px] flex-col justify-center gap-[12px] overflow-hidden pr-[72px]">
            <div className="flex items-center gap-[22px]">
                <span className="flex-none whitespace-nowrap font-saira-cond text-[26px] font-extrabold uppercase tracking-[.05em] text-white">
                    LEVEL DA LIVE
                </span>
                <div className="h-[3px] flex-1 rounded-full bg-brand" />
                <span
                    className={
                        'flex-none whitespace-nowrap font-saira-cond text-[26px] font-extrabold uppercase tracking-[.05em] text-yellow' +
                        (celebrating ? ' animate-pulse' : '')
                    }
                >
                    LV {level}
                </span>
            </div>

            <div className="flex h-[30px] items-center gap-[18px]">
                <div className="h-[16px] flex-1 overflow-hidden rounded-full bg-white/10">
                    <div
                        className="h-full rounded-full bg-[linear-gradient(90deg,var(--color-brand),var(--color-brand-light))]"
                        style={{
                            width: `${fillPercent}%`,
                            transition: `width ${FILL_TRANSITION_MS}ms ease-out`,
                        }}
                    />
                </div>
                {celebrating ? (
                    <span className="flex-none animate-pulse whitespace-nowrap text-[21px] font-extrabold text-yellow">
                        ✨ LEVEL UP!
                    </span>
                ) : (
                    <span className="flex-none whitespace-nowrap text-[21px] font-semibold text-brand-ticker">
                        {formatXp(xp)} / {formatXp(xpToNext)} XP
                    </span>
                )}
            </div>
        </div>
    );
}

export default LevelBar;
