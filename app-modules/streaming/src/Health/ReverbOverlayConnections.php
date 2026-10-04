<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health;

use GuzzleHttp\Exception\GuzzleException;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Pusher\ApiErrorException;
use Pusher\PusherException;

final readonly class ReverbOverlayConnections implements OverlayConnections
{
    public function __construct(
        private BroadcastFactory $broadcast,
    ) {}

    public function count(Streamer $streamer): ?int
    {
        $broadcaster = $this->broadcast->connection();

        if (!$broadcaster instanceof PusherBroadcaster) {
            return null;
        }

        try {
            $info = $broadcaster->getPusher()->getChannelInfo('private-'.$streamer->overlayChannel(), ['info' => 'subscription_count']);
        } catch (ApiErrorException|GuzzleException|PusherException) {
            return null;
        }

        return is_int($info->subscription_count ?? null) ? $info->subscription_count : 0;
    }
}
