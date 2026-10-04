import He4rtLogo from '../He4rtLogo';

export interface StartingBackdropProps {
    done: boolean;
}

const HEART_CX = 1500;
const HEART_CY = 430;
const HEART_SIZE = 760;

export function StartingBackdrop({ done }: StartingBackdropProps) {
    const bloom = done ? 'rgba(255,203,5,.30)' : 'rgba(139,47,232,.42)';

    return (
        <>
            <div className="absolute inset-0 bg-ink-950" />
            <div className="absolute inset-0 bg-[radial-gradient(900px_600px_at_8%_0%,rgba(139,47,232,.28),transparent_70%),radial-gradient(1000px_700px_at_100%_100%,rgba(109,31,208,.30),transparent_70%)]" />

            <div
                className="absolute inset-[-120px] opacity-70 animate-[ssGridDrift_60s_linear_infinite]"
                style={{
                    backgroundImage:
                        'linear-gradient(rgba(201,164,255,.07) 1px, transparent 1px), linear-gradient(90deg, rgba(201,164,255,.07) 1px, transparent 1px)',
                    backgroundSize: '96px 96px',
                    maskImage: 'radial-gradient(1200px 800px at 55% 50%, #000 30%, transparent 85%)',
                    WebkitMaskImage: 'radial-gradient(1200px 800px at 55% 50%, #000 30%, transparent 85%)',
                }}
            />

            {[880, 1120].map((d, i) => (
                <div
                    key={d}
                    className="absolute rounded-full border border-brand/20"
                    style={{
                        width: d,
                        height: d,
                        left: HEART_CX - d / 2,
                        top: HEART_CY - d / 2,
                        borderTopColor: 'rgba(201,164,255,.55)',
                        animation: `spin ${28 + i * 16}s linear infinite ${i ? 'reverse' : ''}`,
                    }}
                />
            ))}

            <div
                className="absolute rounded-full blur-[70px] animate-[ssGlow_4s_ease-in-out_infinite]"
                style={{
                    width: 620,
                    height: 620,
                    left: HEART_CX - 310,
                    top: HEART_CY - 310,
                    background: `radial-gradient(circle, ${bloom} 0%, transparent 65%)`,
                }}
            />
            <div
                className="absolute animate-[ssFloat_6s_ease-in-out_infinite]"
                style={{
                    width: HEART_SIZE,
                    height: HEART_SIZE,
                    left: HEART_CX - HEART_SIZE / 2,
                    top: HEART_CY - HEART_SIZE / 2,
                }}
            >
                <He4rtLogo tone="brand" className="h-full w-full" />
            </div>

            <div className="absolute left-[710px] top-[100px] bottom-[122px] w-[8px] bg-yellow shadow-[0_0_18px_rgba(255,203,5,.45)]" />
        </>
    );
}

export default StartingBackdrop;
