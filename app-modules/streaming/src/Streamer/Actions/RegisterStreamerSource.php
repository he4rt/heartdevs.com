<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Streamer\Events\StreamerSourceRegistered;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class RegisterStreamerSource
{
    public function handle(Streamer $streamer, ExternalIdentity $identity): StreamerSource
    {
        $source = StreamerSource::withTrashed()->createOrFirst([
            'streamer_id' => $streamer->getKey(),
            'external_identity_id' => $identity->getKey(),
        ]);

        if ($source->trashed()) {
            $source->restore();
        }

        if ($source->wasRecentlyCreated) {
            event(new StreamerSourceRegistered($source));
        }

        return $source;
    }
}
