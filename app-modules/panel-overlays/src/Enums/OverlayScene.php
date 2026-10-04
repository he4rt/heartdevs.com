<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Enums;

enum OverlayScene: string
{
    case Coworking = 'coworking';
    case StartingSoon = 'starting';
    case Voice = 'voice';

    public function component(): string
    {
        return match ($this) {
            self::Coworking => 'Overlays/Coworking',
            self::StartingSoon => 'Overlays/StartingSoon',
            self::Voice => 'Overlays/Voice',
        };
    }
}
