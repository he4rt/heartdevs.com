<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Http\Controllers;

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Overlay\OverlayInitialState;
use He4rt\Streaming\Streamer\Actions\ResolveOverlayToken;
use He4rt\Streaming\Streamer\Models\Streamer;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ShowOverlayController
{
    private const int RECENT_CHAT_LIMIT = 30;

    public function __invoke(
        string $token,
        OverlayScene $scene,
        ResolveOverlayToken $resolveToken,
        OverlayInitialState $initialState,
    ): Response {
        $streamer = $resolveToken->handle($token);

        abort_unless($streamer instanceof Streamer, HttpResponse::HTTP_NOT_FOUND);

        return Inertia::render($this->component($scene), [
            'channel' => $streamer->overlayChannel(),
            'authEndpoint' => route('overlays.broadcasting.auth', ['token' => $token]),
            'settings' => $streamer->settings->forScene($scene)->toArray(),
            'recentChat' => $initialState->recentChat($streamer, self::RECENT_CHAT_LIMIT),
            'session' => $initialState->openSession($streamer),
        ]);
    }

    private function component(OverlayScene $scene): string
    {
        return match ($scene) {
            OverlayScene::Coworking => 'Overlays/Coworking',
            OverlayScene::StartingSoon => 'Overlays/StartingSoon',
            OverlayScene::Voice => 'Overlays/Voice',
        };
    }
}
