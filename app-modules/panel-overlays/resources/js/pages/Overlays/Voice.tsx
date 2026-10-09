import { useState } from 'react';
import type { OverlayConfigDto } from '../../feed';
import { useOverlayFeed } from '../../hooks/useOverlayFeed';
import { useVoiceRoster } from '../../hooks/useVoiceRoster';
import type { OverlayPageProps, VoiceSettings } from '../../types';
import VoiceDock, { type DockLayout } from '../../ui/voice/VoiceDock';

const OVERLAY_SLUG = 'voice';

function layoutFrom(dto: OverlayConfigDto): DockLayout | null {
    const layout = dto.overlays[OVERLAY_SLUG]?.values.layout;

    return layout === 'row' || layout === 'col' ? layout : null;
}

export default function VoiceOverlay({ channel, authEndpoint, settings }: OverlayPageProps<VoiceSettings>) {
    const voice = useVoiceRoster();
    const [layout, setLayout] = useState<DockLayout>(settings.layout);

    useOverlayFeed(
        { channel, authEndpoint },
        {
            onVoiceRoster: voice.onDto,
            onOverlayConfig: (dto) => setLayout((current) => layoutFrom(dto) ?? current),
        },
    );

    return <VoiceDock roster={voice.roster} layout={layout} />;
}
