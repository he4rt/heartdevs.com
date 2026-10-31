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

    return { scale, width: Math.ceil(window.innerWidth / scale), height: Math.ceil(window.innerHeight / scale) };
}

export function Stage({ children, fillViewport = false }: StageProps) {
    const [box, setBox] = useState(() => fitStage(fillViewport));

    useEffect(() => {
        const onResize = () => setBox(fitStage(fillViewport));
        onResize();
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, [fillViewport]);

    const anchorStyle: React.CSSProperties = fillViewport
        ? { position: 'absolute', top: 0, left: 0, transformOrigin: '0 0' }
        : { position: 'relative', transformOrigin: 'center' };

    return (
        <div className="fixed inset-0 flex items-center justify-center overflow-hidden bg-transparent">
            <div
                className="flex-none overflow-hidden font-saira"
                style={{
                    ...anchorStyle,
                    width: box.width,
                    height: box.height,
                    transform: `scale(${box.scale})`,
                }}
            >
                {children}
            </div>
        </div>
    );
}
