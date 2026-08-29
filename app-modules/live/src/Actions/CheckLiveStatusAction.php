<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use He4rt\Live\DTOs\LiveStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Consulta a Control API do mediamtx para saber se a live está no ar. */
final readonly class CheckLiveStatusAction
{
    public function execute(): LiveStatus
    {
        try {
            $response = Http::timeout(2)->get(sprintf(
                '%s/v3/paths/get/%s',
                config()->string('live.control_api_url'),
                config()->string('live.path'),
            ));
        } catch (ConnectionException) {
            return LiveStatus::offline();
        }

        if (!$response->ok() || $response->json('ready') !== true) {
            return LiveStatus::offline();
        }

        $readyTime = $response->json('readyTime');

        return new LiveStatus(
            onAir: true,
            startedAt: is_string($readyTime) ? $this->parseReadyTime($readyTime) : null,
        );
    }

    private function parseReadyTime(string $readyTime): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($readyTime);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
