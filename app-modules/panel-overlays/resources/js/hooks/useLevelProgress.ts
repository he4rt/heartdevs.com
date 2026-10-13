import { useCallback, useState } from 'react';
import type { LevelProgressDto } from '../feed';

export interface LevelProgressView {
    level: number;
    xp: number;
    xpToNext: number;
}

const INITIAL: LevelProgressView = { level: 1, xp: 0, xpToNext: 100 };

export function useLevelProgress() {
    const [progress, setProgress] = useState<LevelProgressView>(INITIAL);

    const onDto = useCallback((dto: LevelProgressDto) => {
        setProgress({ level: dto.level, xp: dto.xp, xpToNext: dto.xpToNext });
    }, []);

    return { progress, onDto };
}
