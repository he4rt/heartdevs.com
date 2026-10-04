import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { useVoiceRoster } from '../../hooks/useVoiceRoster';
import VoiceDock, { type DockLayout } from '../../ui/voice/VoiceDock';

function layoutFromQuery(): DockLayout {
    return new URLSearchParams(window.location.search).get('layout') === 'row' ? 'row' : 'col';
}

export default function VoiceOverlay() {
    const voice = useVoiceRoster();

    useOverlayFeed({ onVoiceRoster: voice.onDto });

    return <VoiceDock roster={voice.roster} layout={layoutFromQuery()} />;
}
