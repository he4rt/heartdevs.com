import type { NowPlayingDto } from '../feed';
import type { NowPlayingTrack } from '../ui/footer/NowPlaying';

export function toNowPlaying(dto: NowPlayingDto): NowPlayingTrack | null {
    if (dto.status === 'stopped') return null;

    return {
        title: dto.title,
        artist: dto.artist,
        artUrl: dto.artUrl,
        isPlaying: dto.status === 'playing',
    };
}
