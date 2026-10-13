import { useCallback, useState } from 'react';
import type { NowPlayingDto } from '../feed';
import { toNowPlaying } from '../lib/nowPlaying';
import type { NowPlayingTrack } from '../ui/footer/NowPlaying';

export interface UseNowPlaying {
    track: NowPlayingTrack | null;
    onDto(dto: NowPlayingDto): void;
}

export function useNowPlaying(): UseNowPlaying {
    const [track, setTrack] = useState<NowPlayingTrack | null>(null);

    const onDto = useCallback((dto: NowPlayingDto) => {
        setTrack(toNowPlaying(dto));
    }, []);

    return { track, onDto };
}
