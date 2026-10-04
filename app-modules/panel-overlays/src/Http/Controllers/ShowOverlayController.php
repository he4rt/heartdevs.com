<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Http\Controllers;

use He4rt\PanelOverlays\Enums\OverlayScene;
use Inertia\Inertia;
use Inertia\Response;

class ShowOverlayController
{
    private const string PREVIEW_CHANNEL = 'he4rtdevs';

    public function __invoke(string $token, OverlayScene $scene): Response
    {
        return Inertia::render($scene->component(), [
            'channel' => self::PREVIEW_CHANNEL,
        ]);
    }
}
