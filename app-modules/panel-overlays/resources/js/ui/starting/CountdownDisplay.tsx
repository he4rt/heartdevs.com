import { splitClock } from '../../lib/startingSoon';

export interface CountdownDisplayProps {
    remainingMs: number;
    totalMs: number;
    done: boolean;
}

export function CountdownDisplay({ remainingMs, totalMs, done }: CountdownDisplayProps) {
    const { hh, mm, ss } = splitClock(remainingMs);
    const digitSize = hh ? 'text-[190px]' : 'text-[250px]';
    const colonLift = hh ? 'translate-y-[-14px]' : 'translate-y-[-18px]';
    const progress = totalMs > 0 ? Math.min(1, Math.max(0, remainingMs / totalMs)) : 0;
    const glow = done
        ? '0 0 26px rgba(255,203,5,.85), 0 0 60px rgba(255,203,5,.45)'
        : '0 0 24px rgba(154,60,255,.75), 0 0 64px rgba(139,47,232,.45)';

    return (
        <div className="flex flex-col items-start">
            <div
                className={`flex items-baseline font-saira-cond ${digitSize} font-extrabold leading-[0.9] tracking-[-.02em] tabular-nums ${done ? 'text-yellow' : 'text-white'}`}
                style={{
                    textShadow: glow,
                    animation: done ? 'ssDonePulse 1.2s ease-in-out infinite' : undefined,
                }}
            >
                {hh ? (
                    <>
                        <Block value={hh} />
                        <Colon done={done} lift={colonLift} />
                    </>
                ) : null}
                <Block value={mm} />
                <Colon done={done} lift={colonLift} />
                <Block value={ss} />
            </div>

            <div className="relative mt-[18px] h-[10px] w-[700px] overflow-visible rounded-full bg-white/8 ring-1 ring-brand/30">
                <div
                    className="absolute inset-y-0 left-0 rounded-full bg-[linear-gradient(90deg,var(--color-brand-bright)_0%,var(--color-yellow)_100%)] shadow-[0_0_18px_rgba(255,203,5,.55)]"
                    style={{ width: `${progress * 100}%`, transition: 'width .25s linear' }}
                />
                <div
                    className="absolute top-[-14px] h-0 w-0 -translate-x-1/2 border-x-[9px] border-t-[12px] border-x-transparent border-t-yellow drop-shadow-[0_0_8px_rgba(255,203,5,.8)]"
                    style={{ left: `${progress * 100}%`, transition: 'left .25s linear' }}
                />
            </div>
        </div>
    );
}

function Block({ value }: { value: string }) {
    return (
        <span key={value} className="inline-block animate-[ssTick_.5s_cubic-bezier(.18,.9,.3,1.2)_both]">
            {value}
        </span>
    );
}

function Colon({ done, lift }: { done: boolean; lift: string }) {
    return (
        <span
            className={`mx-[6px] inline-block ${lift} ${done ? 'text-yellow' : 'text-brand-light'}`}
            style={{ animation: done ? undefined : 'ssBlink 1s steps(1,end) infinite' }}
        >
            :
        </span>
    );
}

export default CountdownDisplay;
