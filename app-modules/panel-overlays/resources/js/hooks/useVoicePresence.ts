import { useRef } from 'react';
import type { VoiceRoster } from '../ui/voice/VoiceRoster';

export interface UseVoicePresence {
    present: boolean;
    roster: VoiceRoster | null;
}

export function useVoicePresence(roster: VoiceRoster | null): UseVoicePresence {
    const present = !!roster && roster.members.length >= 1;

    const last = useRef<VoiceRoster | null>(null);
    if (present) last.current = roster;

    return { present, roster: present ? roster : last.current };
}
