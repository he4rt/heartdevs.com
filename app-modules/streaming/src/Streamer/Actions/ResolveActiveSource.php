<?php

declare(strict_types=1);

namespace He4rt\Streaming\Streamer\Actions;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Builder;

final readonly class ResolveActiveSource
{
    public function handle(IdentityProvider $platform, string $broadcasterId): ?StreamerSource
    {
        return StreamerSource::query()
            ->whereHas('identity', fn (Builder $identity): Builder => $identity
                ->where('provider', $platform)
                ->where('external_account_id', $broadcasterId)
                ->whereNotNull('model_id'))
            ->whereHas('streamer', fn (Builder $streamer): Builder => $streamer->where('status', StreamerStatus::Active))
            ->with(['streamer', 'identity'])
            ->first();
    }
}
