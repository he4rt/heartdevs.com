import { useEffect, useState } from 'react';

export interface StageProps {
    children: React.ReactNode;
}

const STAGE_W = 1920;
const STAGE_H = 1080;

function fitScale(): number {
    return Math.min(window.innerWidth / STAGE_W, window.innerHeight / STAGE_H);
}

export function Stage({ children }: StageProps) {
    const [scale, setScale] = useState(fitScale);

    useEffect(() => {
        const onResize = () => setScale(fitScale());
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    return (
        <div className="fixed inset-0 flex items-center justify-center overflow-hidden bg-transparent">
            <div
                className="relative overflow-hidden font-saira"
                style={{
                    width: STAGE_W,
                    height: STAGE_H,
                    transform: `scale(${scale})`,
                    transformOrigin: 'center',
                }}
            >
                {children}
            </div>
        </div>
    );
}
