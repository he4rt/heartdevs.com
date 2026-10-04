<?php

declare(strict_types=1);

namespace He4rt\PanelOverlays\Http\Controllers;

use He4rt\Streaming\Streamer\Actions\ResolveOverlayToken;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeOverlayChannelController
{
    public function __invoke(Request $request, string $token, ResolveOverlayToken $resolveToken): mixed
    {
        $streamer = $resolveToken->handle($token);
        $isStreamerOwnChannel = $streamer instanceof Streamer
            && $request->input('channel_name') === 'private-'.$streamer->overlayChannel();
        $hasPusherSocketId = preg_match('/\A\d+\.\d+\z/', $request->string('socket_id')->toString()) === 1;

        abort_unless($isStreamerOwnChannel && $hasPusherSocketId, Response::HTTP_FORBIDDEN);

        return Broadcast::validAuthenticationResponse($request, result: true);
    }
}
