import { useEffect, useState } from 'react';

export interface Countdown {
    remainingMs: number;
    done: boolean;
}

const TICK_MS = 200;

export function useCountdown(deadline: number): Countdown {
    const [remainingMs, setRemaining] = useState(() => Math.max(0, deadline - Date.now()));

    useEffect(() => {
        const tick = () => setRemaining(Math.max(0, deadline - Date.now()));
        tick();
        const id = setInterval(tick, TICK_MS);
        return () => clearInterval(id);
    }, [deadline]);

    return { remainingMs, done: remainingMs <= 0 };
}
