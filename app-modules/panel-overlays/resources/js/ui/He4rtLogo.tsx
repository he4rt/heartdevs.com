const PATH_A =
    'M321.871 46.0393L142.46 225.416L107.15 190.106L107.144 190.099L107.137 190.093C102.363 185.487 98.5537 179.976 95.9328 173.881C93.3118 167.787 91.9312 161.232 91.8717 154.598C91.8122 147.964 93.0749 141.385 95.5862 135.244C98.0974 129.104 101.807 123.525 106.498 118.834C111.189 114.143 116.767 110.434 122.908 107.923C129.048 105.411 135.628 104.149 142.261 104.208C148.895 104.268 155.451 105.648 161.545 108.269C167.64 110.89 173.151 114.699 177.757 119.473L178.464 120.206L179.183 119.486L251.253 47.4526L251.969 46.7366L251.244 46.0295C222.277 17.7749 183.344 2.07155 142.88 2.3217C102.416 2.57185 63.6803 18.7554 35.065 47.3659C6.44979 75.9765 -9.74008 114.71 -9.9969 155.174C-10.2537 195.638 5.44322 234.573 33.693 263.545L33.7019 263.554L141.753 371.604L142.46 372.311L143.167 371.604L497.208 17.5631L498.208 16.5632L496.932 15.9537C476.231 6.06282 453.572 0.952379 430.63 1.00033C410.428 0.965204 390.418 4.92639 371.753 12.6559C353.087 20.3856 336.134 31.7312 321.871 46.0393Z';
const PATH_B =
    'M569.434 88.4943L568.824 87.2175L567.824 88.218L430.627 225.45L395.317 190.106L394.61 189.398L393.903 190.105L321.869 262.139L321.162 262.846L321.869 263.553L357.179 298.863L213.818 442.224L213.112 442.93L213.818 443.638L285.852 515.707L286.559 516.414L287.266 515.707L539.384 263.589L538.677 262.882L539.385 263.589C561.877 241.089 576.843 212.171 582.224 180.815C587.605 149.459 583.137 117.206 569.434 88.4943Z';

interface TonePreset {
    trace: string;
    traceWidth: number;
    led: string;
    ledB: string;
    ledWidth: number;
    glow: string;
}

const TONES: Record<'light' | 'brand', TonePreset> = {
    light: {
        trace: 'rgba(255,255,255,0.28)',
        traceWidth: 13,
        led: '#ffffff',
        ledB: 'var(--color-brand-pale)',
        ledWidth: 21,
        glow: 'drop-shadow(0 0 4px rgba(255,255,255,0.95)) drop-shadow(0 0 10px rgba(201,164,255,0.85))',
    },
    brand: {
        trace: 'rgba(139,47,232,0.16)',
        traceWidth: 1.8,
        led: 'var(--color-brand-light)',
        ledB: 'var(--color-brand)',
        ledWidth: 3.4,
        glow: 'drop-shadow(0 0 5px var(--color-brand-bright)) drop-shadow(0 0 13px rgba(139,47,232,0.9))',
    },
};

export interface He4rtLogoProps {
    tone?: 'light' | 'brand';
    className?: string;
    style?: React.CSSProperties;
}

export default function He4rtLogo({ tone = 'light', className, style }: He4rtLogoProps) {
    const t = TONES[tone];

    const traceStyle: React.CSSProperties = {
        fill: 'none',
        stroke: t.trace,
        strokeWidth: t.traceWidth,
    };

    const ledBase: React.CSSProperties = {
        fill: 'none',
        strokeWidth: t.ledWidth,
        strokeLinecap: 'round',
        strokeDasharray: '8 92',
        strokeDashoffset: 100,
        filter: t.glow,
    };

    return (
        <svg
            viewBox="-24 -16 632 548"
            fill="none"
            preserveAspectRatio="xMidYMid meet"
            aria-hidden="true"
            className={className}
            style={style}
        >
            <path d={PATH_A} style={traceStyle} />
            <path d={PATH_B} style={traceStyle} />
            <path
                d={PATH_A}
                pathLength={100}
                style={{
                    ...ledBase,
                    stroke: t.led,
                    animation: 'heartLed 5.4s linear 0s infinite',
                }}
            />
            <path
                d={PATH_B}
                pathLength={100}
                style={{
                    ...ledBase,
                    stroke: t.ledB,
                    animation: 'heartLed 7.2s linear -3s infinite',
                }}
            />
        </svg>
    );
}
