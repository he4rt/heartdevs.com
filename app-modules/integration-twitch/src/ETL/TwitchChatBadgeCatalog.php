<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\ETL;

use He4rt\IntegrationTwitch\Transport\Requests\Chat\GetChannelChatBadges;
use He4rt\IntegrationTwitch\Transport\Requests\Chat\GetGlobalChatBadges;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Request;

final readonly class TwitchChatBadgeCatalog
{
    private const int TTL_SECONDS = 21_600;

    private const int RETRY_AFTER_SECONDS = 300;

    /**
     * Channel badges come last, so a channel's own subscriber badge replaces the global one.
     *
     * @return array<string, string> image URL keyed by "set_id/version"
     */
    public function forChannel(string $broadcasterId): array
    {
        return [
            ...$this->cached('twitch_chat_badges:global', new GetGlobalChatBadges()),
            ...$this->cached('twitch_chat_badges:'.$broadcasterId, new GetChannelChatBadges($broadcasterId)),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function cached(string $key, Request $request): array
    {
        $cached = Cache::get($key);

        if (is_array($cached)) {
            /** @var array<string, string> $cached */
            return $cached;
        }

        try {
            $sets = resolve(TwitchHelixConnector::class)->send($request)->json('data');
        } catch (SaloonException $saloonException) {
            Log::warning('Twitch chat badge lookup failed', ['cache_key' => $key, 'error' => $saloonException->getMessage()]);
            Cache::put($key, [], self::RETRY_AFTER_SECONDS);

            return [];
        }

        $urls = $this->urlsOf(is_array($sets) ? $sets : []);
        Cache::put($key, $urls, self::TTL_SECONDS);

        return $urls;
    }

    /**
     * @param  array<array-key, mixed>  $sets
     * @return array<string, string>
     */
    private function urlsOf(array $sets): array
    {
        $urls = [];

        foreach ($sets as $set) {
            $setId = is_array($set) ? ($set['set_id'] ?? null) : null;
            $versions = is_array($set) ? ($set['versions'] ?? null) : null;

            if (!is_string($setId) || !is_array($versions)) {
                continue;
            }

            foreach ($versions as $version) {
                $id = is_array($version) ? ($version['id'] ?? null) : null;
                $url = is_array($version) ? ($version['image_url_2x'] ?? null) : null;

                if (is_string($id) && is_string($url)) {
                    $urls[$setId.'/'.$id] = $url;
                }
            }
        }

        return $urls;
    }
}
