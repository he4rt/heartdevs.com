<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\Console;

use Carbon\CarbonImmutable;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\IntegrationDiscord\ETL\Actions\ImportDiscordVoiceLogAction;
use He4rt\IntegrationDiscord\ETL\DTOs\DiscordVoiceLogDTO;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Channels\ListGuildChannels;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\ListChannelMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\warning;

#[Description('Backfill voice logs by paginating a Discord channel where a bot logs voice join/left events')]
#[Signature('discord:backfill-voice
        {channel_id : Discord channel ID where the bot posts voice join/left logs}
        {--since= : Start date (Y-m-d). Defaults to 2026-03-01}
        {--until= : End date (Y-m-d). Defaults to now}
        {--bot-id=621538099545112596 : Discord bot user ID that posts voice logs (Dyno)}
        {--tenant=he4rt : Tenant slug}
        {--dry-run : Preview without saving}')]
final class BackfillVoiceLogsCommand extends Command
{
    private int $voiceCount = 0;

    private int $joinCount = 0;

    private int $leftCount = 0;

    private int $skippedCount = 0;

    private int $rateLimitHits = 0;

    private int $alreadyExistsCount = 0;

    public function handle(
        DiscordConnector $connector,
        ImportDiscordVoiceLogAction $voiceAction,
    ): int {
        DB::disableQueryLog();

        $tenantId = (string) config('he4rt.tenant_id');

        if ($tenantId === '') {
            error('No tenant configured (set HE4RT_TENANT_ID).');

            return self::FAILURE;
        }

        $channelId = (string) $this->argument('channel_id');
        $botId = (string) $this->option('bot-id');
        $isDryRun = (bool) $this->option('dry-run');
        $since = CarbonImmutable::parse($this->option('since') ?? '2026-03-01');
        $until = CarbonImmutable::parse($this->option('until') ?? 'now');

        intro(sprintf('Discord Voice Backfill%s', $isDryRun ? ' [DRY RUN]' : ''));

        $existingCount = Voice::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [$since, $until])
            ->count();

        table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Channel', $channelId],
                ['Bot filter', $botId],
                ['Period', sprintf('%s → %s', $since->toDateString(), $until->toDateString())],
                ['Tenant', $tenantId],
                ['Existing voice events in period', number_format($existingCount)],
            ],
        );

        $channelMap = $this->buildChannelMap($connector);
        note(sprintf('Channel map loaded: %d channels resolved', count($channelMap)));

        $before = null;
        $pages = 0;
        $fetched = 0;

        task(
            label: 'Fetching voice logs [starting...]',
            callback: function ($logger) use (
                $connector, $voiceAction, $channelId, $botId,
                $isDryRun, $since, $until, $tenantId, $channelMap,
                &$before, &$pages, &$fetched,
            ): void {
                $reachedSince = false;

                while (!$reachedSince) {
                    $response = $connector->send(new ListChannelMessages(
                        channelId: $channelId,
                        limit: 100,
                        before: $before,
                    ));

                    if ($response->status() === 429) {
                        $retryAfter = (int) ($response->json('retry_after') ?? 5);
                        $this->rateLimitHits++;
                        $logger->warning(sprintf(
                            'Rate limited — pausing %ds (hit #%d)',
                            $retryAfter,
                            $this->rateLimitHits,
                        ));
                        Sleep::sleep($retryAfter + 1);

                        continue;
                    }

                    if ($response->failed()) {
                        $logger->warning(sprintf('HTTP %d — aborting', $response->status()));

                        break;
                    }

                    /** @var list<array<string, mixed>> $messages */
                    $messages = $response->json();

                    if ($messages === []) {
                        break;
                    }

                    $pages++;
                    $fetched += count($messages);
                    $oldestTimestamp = '';

                    foreach ($messages as $message) {
                        $timestamp = CarbonImmutable::parse($message['timestamp']);
                        $oldestTimestamp = $timestamp->timezone(config('app.display_timezone'))->format('Y-m-d H:i');

                        if ($timestamp->isBefore($since)) {
                            $reachedSince = true;

                            break;
                        }

                        if ($timestamp->isAfter($until)) {
                            continue;
                        }

                        $authorId = $message['author']['id'] ?? null;

                        if ($authorId !== $botId) {
                            $this->skippedCount++;

                            continue;
                        }

                        $voiceDto = DiscordVoiceLogDTO::fromDump($message);

                        if (!$voiceDto instanceof DiscordVoiceLogDTO) {
                            $this->skippedCount++;

                            continue;
                        }

                        $channelName = $channelMap[$voiceDto->voiceChannelId] ?? $voiceDto->voiceChannelId;
                        $exists = Voice::query()
                            ->where('tenant_id', $tenantId)
                            ->where('provider_message_id', (string) $message['id'])
                            ->exists();

                        if ($exists) {
                            $this->alreadyExistsCount++;
                            $logger->line(sprintf(
                                '%s <@%s> %s #%s [EXISTS]',
                                $timestamp->timezone(config('app.display_timezone'))->format('m/d H:i'),
                                $voiceDto->userDiscordId,
                                $voiceDto->action,
                                $channelName,
                            ));
                        } else {
                            if (!$isDryRun) {
                                $voiceAction->handle($voiceDto, $tenantId, $channelMap);
                            }

                            $this->voiceCount++;

                            if ($voiceDto->action === 'joined') {
                                $this->joinCount++;
                            } else {
                                $this->leftCount++;
                            }

                            $logger->line(sprintf(
                                '%s <@%s> %s #%s [NEW]',
                                $timestamp->timezone(config('app.display_timezone'))->format('m/d H:i'),
                                $voiceDto->userDiscordId,
                                $voiceDto->action,
                                $channelName,
                            ));
                        }
                    }

                    $before = end($messages)['id'] ?? null;

                    if ($before === null) {
                        break;
                    }

                    $logger->label(sprintf(
                        'Page %d | oldest: %s | New: %d (↗%d ↘%d) | Exists: %d | Skip: %d | 429s: %d',
                        $pages,
                        $oldestTimestamp,
                        $this->voiceCount,
                        $this->joinCount,
                        $this->leftCount,
                        $this->alreadyExistsCount,
                        $this->skippedCount,
                        $this->rateLimitHits,
                    ));

                    Sleep::usleep(500_000);
                }
            },
            limit: 15,
        );

        $this->newLine();

        if ($this->rateLimitHits > 0) {
            warning(sprintf('Hit rate limit %d time(s) during backfill.', $this->rateLimitHits));
        }

        table(
            headers: ['Metric', 'Value'],
            rows: [
                ['Period', sprintf('%s → %s', $since->toDateString(), $until->toDateString())],
                ['Pages fetched', number_format($pages)],
                ['Messages scanned', number_format($fetched)],
                ['New voice events', number_format($this->voiceCount)],
                ['  ↗ Joins', number_format($this->joinCount)],
                ['  ↘ Leaves', number_format($this->leftCount)],
                ['Already in DB', number_format($this->alreadyExistsCount)],
                ['Messages skipped', number_format($this->skippedCount)],
                ['Rate limit hits', (string) $this->rateLimitHits],
                ['Mode', $isDryRun ? 'DRY RUN' : 'LIVE'],
            ],
        );

        outro(sprintf(
            'Backfill %s — %s voice events %s',
            $isDryRun ? 'preview done' : 'complete',
            number_format($this->voiceCount),
            $isDryRun ? 'would be imported' : 'imported',
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function buildChannelMap(DiscordConnector $connector): array
    {
        $guildId = config('he4rt.discord.guild_id');

        if ($guildId === null) {
            return [];
        }

        $response = $connector->send(new ListGuildChannels((string) $guildId));

        /** @var list<array<string, mixed>> $channels */
        $channels = $response->json();

        $map = [];

        foreach ($channels as $channel) {
            if (isset($channel['id'], $channel['name'])) {
                $map[(string) $channel['id']] = $channel['name'];
            }
        }

        return $map;
    }
}
