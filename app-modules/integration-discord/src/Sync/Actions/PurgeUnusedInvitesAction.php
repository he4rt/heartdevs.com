<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Actions;

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\DeleteInvite;
use He4rt\IntegrationDiscord\Transport\Requests\Invites\ListGuildInvites;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use RuntimeException;
use Throwable;

final readonly class PurgeUnusedInvitesAction
{
    public function __construct(
        private DiscordConnector $connector,
    ) {}

    /**
     * @return array{total: int, matched: int, deleted: int, failed: int, invites: list<array{code: string, channel: string, inviter: string, created_at: string}>}
     */
    public function execute(string $guildId, bool $dryRun = false, bool $includeExpiring = false): array
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
            static fn (array $invite): array => [
                'code' => $invite['code'],
                'channel' => $invite['channel']['name'] ?? 'unknown',
                'inviter' => $invite['inviter']['username'] ?? 'unknown',
                'created_at' => isset($invite['created_at'])
                    ? Date::parse($invite['created_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i')
                    : '',
            ],
            $unused,
        ));

        if ($dryRun) {
            return [
                'total' => count($allInvites),
                'matched' => count($unused),
                'deleted' => 0,
                'failed' => 0,
                'invites' => $matches,
            ];
        }

        $deleted = 0;
        $failed = 0;

        foreach ($unused as $index => $invite) {
            if ($index > 0) {
                Sleep::usleep(random_int(200_000, 500_000));
            }

            try {
                $response = $this->connector->send(new DeleteInvite($invite['code']));

                if ($response->failed()) {
                    throw new RuntimeException(sprintf('HTTP %d: %s', $response->status(), $response->body()));
                }

                $deleted++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning('Failed to delete Discord invite', [
                    'code' => $invite['code'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total' => count($allInvites),
            'matched' => count($unused),
            'deleted' => $deleted,
            'failed' => $failed,
            'invites' => $matches,
        ];
    }
}
