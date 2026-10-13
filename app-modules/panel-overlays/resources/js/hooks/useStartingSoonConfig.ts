import { useCallback, useState } from 'react';
import type { OverlayConfigDto } from '../feed';
import { readStartingSoonConfig, withServerValues, type StartingSoonConfig } from '../lib/startingSoon';

export interface UseStartingSoonConfig {
    config: StartingSoonConfig;
    onDto(dto: OverlayConfigDto): void;
}

const OVERLAY_SLUG = 'starting';

function configFrom(dto: OverlayConfigDto, fallback: StartingSoonConfig): StartingSoonConfig | null {
    const stored = dto.overlays[OVERLAY_SLUG];

    return stored ? withServerValues(fallback, stored.values, stored.since) : null;
}

export function useStartingSoonConfig(initial: OverlayConfigDto): UseStartingSoonConfig {
    const [fallback] = useState(readStartingSoonConfig);
    const [config, setConfig] = useState<StartingSoonConfig>(() => configFrom(initial, fallback) ?? fallback);

    const onDto = useCallback(
        (dto: OverlayConfigDto) => {
            const next = configFrom(dto, fallback);
            if (next) setConfig(next);
        },
        [fallback],
    );

    return { config, onDto };
}
