<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Actions;

use He4rt\IntegrationDiscord\Sync\DTOs\MatchedInviteDTO;
use He4rt\IntegrationDiscord\Sync\DTOs\PurgeInvitesResultDTO;
use He4rt\IntegrationDiscord\Sync\Exceptions\CloudflareIpBanException;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\DeleteInvite;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\ListGuildInvites;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use JsonException;
use Random\RandomException;
use RuntimeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;

final readonly class PurgeUnusedInvitesAction
{
    private const int MAX_RETRIES = 3;

    public function __construct(
        private DiscordConnector $connector,
    ) {}

    /**
     * @throws RandomException
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    public function execute(string $guildId, bool $dryRun = false, bool $includeExpiring = false): PurgeInvitesResultDTO
    {
        $response = $this->connector->send(new ListGuildInvites($guildId));

        if ($response->failed()) {
            throw new RuntimeException(sprintf('Failed to list guild invites: HTTP %d', $response->status()));
        }

        /** @var list<array<string, mixed>> $allInvites */
        $allInvites = $response->json();

        $unused = array_filter(
            $allInvites,
            static fn (array $invite): bool => ($invite['uses'] ?? -1) === 0
                && ($includeExpiring || ($invite['max_age'] ?? -1) === 0),
        );

        $matches = array_values(array_map(
            MatchedInviteDTO::fromDiscordApi(...),
            $unused,
        ));

        if ($dryRun) {
            return PurgeInvitesResultDTO::fromDryRun(total: count($allInvites), invites: $matches);
        }

        $deleted = 0;
        $failed = 0;

        foreach (array_values($unused) as $index => $invite) {
            if ($index > 0) {
                $this->jitteredSleep();
            }

            try {
                $this->deleteInvite($invite['code']);
                $deleted++;
            } catch (CloudflareIpBanException $e) {
                $failed += count($unused) - $index;
                Log::warning('Cloudflare IP ban detected, aborting purge', [
                    'code' => $invite['code'],
                    'error' => $e->getMessage(),
                ]);

                break;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('Failed to delete Discord invite', [
                    'code' => $invite['code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return PurgeInvitesResultDTO::fromPurge(
            total: count($allInvites),
            invites: $matches,
            deleted: $deleted,
            failed: $failed,
        );
    }

    /**
     * @throws RandomException
     */
    private function jitteredSleep(float $baseSeconds = 0.0): void
    {
        $jitter = random_int(3_000, 6_000) / 1_000;

        Sleep::for($baseSeconds + $jitter)->seconds();
    }

    /**
     * @throws CloudflareIpBanException
     * @throws RandomException
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    private function deleteInvite(string $code): void
    {
        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            $response = $this->connector->send(new DeleteInvite($code));

            if ($response->successful()) {
                return;
            }

            if ($response->status() === 429) {
                $retryAfter = $this->parseRetryAfter($response);

                if ($retryAfter > 60.0) {
                    throw new CloudflareIpBanException(sprintf('Retry-After %ds', (int) $retryAfter));
                }

                if ($attempt < self::MAX_RETRIES) {
                    $this->jitteredSleep($retryAfter);

                    continue;
                }
            }

            throw new RuntimeException(sprintf('HTTP %d: %s', $response->status(), $response->body()));
        }
    }

    private function parseRetryAfter(Response $response): float
    {
        $retryAfter = Arr::get(json_decode($response->body(), associative: true), 'retry_after');

        if ($retryAfter !== null) {
            return (float) $retryAfter;
        }

        $resetAfter = $response->header('X-RateLimit-Reset-After');

        if ($resetAfter !== '' && is_numeric($resetAfter)) {
            return (float) $resetAfter;
        }

        $header = $response->header('Retry-After');

        if ($header !== '' && is_numeric($header)) {
            return (float) $header;
        }

        return 1.0;
    }
}
