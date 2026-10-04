<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Queries;

use Carbon\CarbonInterface;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Streamer\Data\MutedChatters;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Builder;

final readonly class StreamerChatMessages
{
    /**
     * Messages from the channels of every source that shows chat on the overlay.
     *
     * @return Builder<Message>
     */
    public function of(Streamer $streamer): Builder
    {
        $chatSources = $streamer->sources()
            ->with('identity')
            ->get()
            ->filter(fn (StreamerSource $source): bool => $source->showsChat());

        return Message::query()->where(function (Builder $query) use ($chatSources): void {
            if ($chatSources->isEmpty()) {
                $query->whereRaw('false');

                return;
            }

            foreach ($chatSources as $source) {
                $query->orWhere(fn (Builder $channel): Builder => $channel
                    ->where('platform', $source->identity->provider)
                    ->where('channel_id', $source->identity->external_account_id));
            }
        });
    }

    /**
     * @return Builder<Message>
     */
    public function onOverlay(Streamer $streamer): Builder
    {
        $mutedChatters = $streamer->settings->mutedChatters;

        return $this->of($streamer)
            ->whereNull('metadata->deleted_at')
            ->whereNull('metadata->hidden_at')
            ->when($streamer->chat_cleared_at instanceof CarbonInterface, fn (Builder $query): Builder => $query->where('sent_at', '>', $streamer->chat_cleared_at))
            ->unless($mutedChatters->isEmpty(), fn (Builder $query): Builder => $query->whereDoesntHave(
                'provider',
                fn (Builder $identity): Builder => $identity->where(fn (Builder $muted): Builder => $this->matchingAny($muted, $mutedChatters)),
            ));
    }

    /**
     * @param  Builder<ExternalIdentity>  $identities
     * @return Builder<ExternalIdentity>
     */
    private function matchingAny(Builder $identities, MutedChatters $mutedChatters): Builder
    {
        foreach ($mutedChatters->chatters as $chatter) {
            $identities->orWhere(fn (Builder $identity): Builder => $identity
                ->where('provider', $chatter->platform)
                ->where('external_account_id', $chatter->chatterId));
        }

        return $identities;
    }
}
