<?php

declare(strict_types=1);

namespace He4rt\Live\Actions;

use He4rt\Live\DTOs\IngestAuthPayload;

/** Decide se o mediamtx pode executar a ação solicitada: leitura é pública, publish exige a stream key. */
final readonly class AuthorizeMediaServerAction
{
    public function execute(IngestAuthPayload $payload): bool
    {
        return match ($payload->action) {
            'read', 'playback' => true,
            'publish' => $this->authorizePublish($payload),
            default => false,
        };
    }

    private function authorizePublish(IngestAuthPayload $payload): bool
    {
        $streamKey = config()->string('live.stream_key');

        if ($streamKey === '') {
            return false;
        }

        return hash_equals($streamKey, $payload->password);
    }
}
