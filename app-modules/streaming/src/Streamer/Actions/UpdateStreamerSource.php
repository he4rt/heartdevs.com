<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Streamer\Events\StreamerSourceUpdated;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class UpdateStreamerSource
{
    public function handle(StreamerSource $source, bool $enabled, ?ChatReader $chatReader): StreamerSource
    {
        $source->fill([
            'enabled' => $enabled,
            'chat_reader' => $chatReader,
        ])->save();

        if ($source->wasChanged(['enabled', 'chat_reader'])) {
            event(new StreamerSourceUpdated($source));
        }

        return $source;
    }
}
