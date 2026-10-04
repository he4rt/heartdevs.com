import { useCallback, useState } from 'react';
import type { OverlayConfigDto } from '../feed';
import { configFromServer, readStartingSoonConfig, type StartingSoonConfig } from '../lib/startingSoon';

export interface UseStartingSoonConfig {
    config: StartingSoonConfig;
    onDto(dto: OverlayConfigDto): void;
}

const OVERLAY_SLUG = 'starting';

export function useStartingSoonConfig(): UseStartingSoonConfig {
    const [config, setConfig] = useState<StartingSoonConfig>(readStartingSoonConfig);

    const onDto = useCallback((dto: OverlayConfigDto) => {
        const stored = dto.overlays[OVERLAY_SLUG];
        if (!stored) return;

        const next = configFromServer(stored.values, stored.since);
        if (next) setConfig(next);
    }, []);

    return { config, onDto };
}
