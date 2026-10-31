import { useEffect, useState } from 'react';

export interface StageProps {
    children: React.ReactNode;
    fillViewport?: boolean;
}

interface StageBox {
    scale: number;
    width: number;
    height: number;
}

const STAGE_W = 1920;
const STAGE_H = 1080;

function fitStage(fillViewport: boolean): StageBox {
    const scale = Math.min(window.innerWidth / STAGE_W, window.innerHeight / STAGE_H);

    if (!fillViewport) {
        return { scale, width: STAGE_W, height: STAGE_H };
    }

    return { scale, width: window.innerWidth / scale, height: window.innerHeight / scale };
}

export function Stage({ children, fillViewport = false }: StageProps) {
    const [box, setBox] = useState(() => fitStage(fillViewport));

    useEffect(() => {
        const onResize = () => setBox(fitStage(fillViewport));
        onResize();
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, [fillViewport]);

    return (
        <div className="fixed inset-0 flex items-center justify-center overflow-hidden bg-transparent">
            <div
                className="relative flex-none overflow-hidden font-saira"
                style={{
                    width: box.width,
                    height: box.height,
                    transform: `scale(${box.scale})`,
                    transformOrigin: 'center',
                }}
            >
                {children}
            </div>
        </div>
    );
}
