<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health\Checks;

use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class CheckOverlayConnections
{
    public const string KEY = 'overlay_connections';

    private const string TITLE = 'Overlays conectadas';

    public function __construct(
        private OverlayConnections $connections,
    ) {}

    public function handle(Streamer $streamer): HealthCheck
    {
        $count = $this->connections->count($streamer);

        if ($count === null) {
            return new HealthCheck(self::KEY, HealthStatus::Error, self::TITLE, 'O servidor de tempo real não respondeu. As overlays não recebem eventos.');
        }

        if ($count === 0) {
            return $streamer->isLive()
                ? new HealthCheck(self::KEY, HealthStatus::Warning, self::TITLE, 'Você está ao vivo e nenhuma overlay está aberta.')
                : new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, 'Nenhuma overlay aberta agora.');
        }

        return new HealthCheck(self::KEY, HealthStatus::Ok, self::TITLE, $count === 1 ? '1 overlay aberta' : sprintf('%d overlays abertas', $count));
    }
}
