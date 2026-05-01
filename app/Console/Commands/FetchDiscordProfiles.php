<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\warning;

class FetchDiscordProfiles extends Command
{
    private const string STATUS_DIR = 'discord';

    private const string STATUS_FILE = 'discord/profiles_status.csv';

    private const int CHUNK_SIZE = 1000;

    protected $signature = 'discord:fetch-profiles
        {--token= : Discord user token (or set DISCORD_USER_TOKEN in .env)}
        {--members-file=discord/members.json : Path to members JSON}
        {--limit=0 : Max profiles to fetch this run (0 = all remaining)}
        {--retry-failed : Also retry previously failed IDs}';

    protected $description = 'Fetch full Discord profiles for all members, tracking progress across runs';

    private int $successCount = 0;

    private int $failCount = 0;

    private int $githubCount = 0;

    private int $rateLimitHits = 0;

    public function handle(): void
    {
        $token = $this->option('token') ?: env('DISCORD_USER_TOKEN');

        if (!$token) {
            error('Discord user token not provided. Use --token or set DISCORD_USER_TOKEN in .env');

            return;
        }

        $membersFile = $this->option('members-file');

        if (!Storage::disk('local')->exists($membersFile)) {
            error('Members file not found: '.$membersFile);
            info('Run `php artisan discord:fetch-members` first.');

            return;
        }

        intro('Discord Profile Scraper');

        // Load members
        $membersData = json_decode((string) Storage::disk('local')->get($membersFile), true);
        $allIds = array_map(fn (array $m) => (string) $m['user']['id'], $membersData['members'] ?? []);

        info('Loaded '.count($allIds).' member IDs from '.$membersFile);

        // Load or create status CSV
        $statusMap = $this->loadStatusCsv();
        $newIds = 0;

        foreach ($allIds as $id) {
            if (!isset($statusMap[$id])) {
                $statusMap[$id] = ['status' => 'waiting', 'updated_at' => ''];
                $newIds++;
            }
        }

        if ($newIds > 0) {
            $this->saveStatusCsv($statusMap);
        }

        // Filter IDs to process
        $retryFailed = $this->option('retry-failed');
        $pending = [];

        foreach ($statusMap as $id => $entry) {
            if ($entry['status'] === 'waiting') {
                $pending[] = $id;
            } elseif ($retryFailed && $entry['status'] === 'failed') {
                $pending[] = $id;
            }
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $pending = array_slice($pending, 0, $limit);
        }

        $totalSuccess = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'success'));
        $totalFailed = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'failed'));
        $totalWaiting = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'waiting'));

        // Show current state
        table(
            headers: ['Total Tracked', 'Success', 'Failed', 'Waiting', 'This Run'],
            rows: [[count($statusMap), $totalSuccess, $totalFailed, $totalWaiting, count($pending)]],
        );

        if ($pending === []) {
            outro('Nothing to fetch — all profiles already scraped!');

            return;
        }

        $estimatedMinutes = (int) ceil(count($pending) * 1.7 / 60);
        note(sprintf('Estimated time: ~%d minutes (', $estimatedMinutes).count($pending).' profiles at ~1.7s each)');

        // Main scraping loop
        $guildId = config('he4rt.discord.guild_id');
        $this->successCount = 0;
        $this->failCount = 0;
        $this->githubCount = 0;
        $this->rateLimitHits = 0;
        $processed = 0;
        $total = count($pending);

        task(
            label: sprintf('Fetching profiles [0/%d — 0%%]', $total),
            callback: function ($logger) use ($token, $guildId, &$statusMap, $totalSuccess, &$processed, $total, $pending): void {
                foreach ($pending as $discordId) {
                    $profile = $this->fetchProfile($token, $discordId, $guildId);
                    $now = now()->toISOString();

                    if ($profile === null) {
                        $statusMap[$discordId] = ['status' => 'failed', 'updated_at' => $now];
                        $this->failCount++;

                        $logger->warning(sprintf('FAIL %s — request failed', $discordId));
                    } else {
                        $statusMap[$discordId] = ['status' => 'success', 'updated_at' => $now];
                        $this->appendToChunk($discordId, $profile, $totalSuccess + $this->successCount);
                        $this->successCount++;

                        $username = $profile['user']['username'] ?? '?';
                        $globalName = $profile['user']['global_name'] ?? $username;
                        $accounts = $profile['connected_accounts'] ?? [];
                        $github = collect($accounts)->firstWhere('type', 'github');
                        $connTypes = collect($accounts)->pluck('type')->implode(', ') ?: 'none';

                        if ($github) {
                            $this->githubCount++;
                            $logger->success(sprintf('%s (@%s) — github:%s — [%s]', $globalName, $username, $github['name'], $connTypes));
                        } else {
                            $logger->line(sprintf('%s (@%s) — [%s]', $globalName, $username, $connTypes));
                        }
                    }

                    $processed++;

                    // Update label with progress
                    $pct = round($processed / $total * 100, 1);
                    $stats = sprintf('OK:%d Fail:%d GH:%d 429s:%d', $this->successCount, $this->failCount, $this->githubCount, $this->rateLimitHits);
                    $logger->label(sprintf('Fetching profiles [%d/%d — %s%%] %s', $processed, $total, $pct, $stats));

                    // Save status CSV every 10 requests
                    if ($processed % 10 === 0) {
                        $this->saveStatusCsv($statusMap);
                    }

                    // Jittered delay: 1.5s + random 0.2-0.3s
                    $jitter = random_int(200, 300);
                    Sleep::usleep((1_500 + $jitter) * 1000);
                }
            },
            limit: 10,
        );

        // Final save
        $this->saveStatusCsv($statusMap);

        // Recalculate totals
        $totalSuccess = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'success'));
        $totalFailed = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'failed'));
        $totalWaiting = count(array_filter($statusMap, fn (array $e) => $e['status'] === 'waiting'));

        // Final summary
        table(
            headers: ['Metric', 'This Run', 'Overall'],
            rows: [
                ['Profiles fetched', $this->successCount, $totalSuccess],
                ['Failed', $this->failCount, $totalFailed],
                ['Remaining', '', $totalWaiting],
                ['GitHub found', $this->githubCount, ''],
                ['Rate limits hit', $this->rateLimitHits, ''],
            ],
        );

        if ($totalWaiting > 0) {
            warning($totalWaiting.' profiles still waiting. Run the command again to continue.');
        }

        outro('Done! Profiles saved to storage/app/private/discord/profiles_chunk_*.json');
    }

    private function fetchProfile(string $token, string|int $userId, string $guildId): ?array
    {
        $maxRetries = 3;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->get(sprintf('https://discord.com/api/v9/users/%s/profile', $userId), [
                'type' => 'popout',
                'with_mutual_guilds' => 'false',
                'with_mutual_friends' => 'false',
                'with_mutual_friends_count' => 'false',
                'guild_id' => $guildId,
            ]);

            if ($response->status() === 429) {
                $retryAfter = $response->json('retry_after', 5);
                $this->rateLimitHits++;
                Sleep::sleep((int) ceil($retryAfter));

                continue;
            }

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        }

        return null;
    }

    private function appendToChunk(string|int $discordId, array $profile, int $currentSuccessTotal): void
    {
        $chunkIndex = (int) floor($currentSuccessTotal / self::CHUNK_SIZE);
        $chunkFile = self::STATUS_DIR.sprintf('/profiles_chunk_%d.json', $chunkIndex);

        $existing = [];

        if (Storage::disk('local')->exists($chunkFile)) {
            $existing = json_decode((string) Storage::disk('local')->get($chunkFile), true) ?? [];
        }

        $existing[$discordId] = $profile;

        Storage::disk('local')->put(
            $chunkFile,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function loadStatusCsv(): array
    {
        $map = [];

        if (!Storage::disk('local')->exists(self::STATUS_FILE)) {
            return $map;
        }

        $content = Storage::disk('local')->get(self::STATUS_FILE);
        $lines = explode("\n", mb_trim($content));

        // Skip header
        array_shift($lines);

        foreach ($lines as $line) {
            if ($line === '' || $line === '0') {
                continue;
            }

            [$id, $status, $updatedAt] = str_getcsv($line);
            $map[$id] = ['status' => $status, 'updated_at' => $updatedAt];
        }

        return $map;
    }

    private function saveStatusCsv(array $statusMap): void
    {
        $csv = "discord_id,status,updated_at\n";

        foreach ($statusMap as $id => $entry) {
            $csv .= sprintf('%s,%s,%s%s', $id, $entry['status'], $entry['updated_at'], PHP_EOL);
        }

        Storage::disk('local')->put(self::STATUS_FILE, $csv);
    }
}
