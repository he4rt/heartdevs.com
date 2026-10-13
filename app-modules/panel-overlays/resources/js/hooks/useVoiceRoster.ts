import { useCallback, useState } from 'react';
import type { VoiceRosterDto } from '../feed';
import { toVoiceRoster } from '../lib/voiceRoster';
import type { VoiceRoster } from '../ui/voice/VoiceRoster';

export interface UseVoiceRoster {
    roster: VoiceRoster | null;
    onDto(dto: VoiceRosterDto): void;
}

export function useVoiceRoster(): UseVoiceRoster {
    const [roster, setRoster] = useState<VoiceRoster | null>(null);

    const onDto = useCallback((dto: VoiceRosterDto) => {
        setRoster(toVoiceRoster(dto));
    }, []);

    return { roster, onDto };
}
