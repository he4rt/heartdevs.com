<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

#[Description('Analyze scraped Discord profiles and count connected social accounts')]
#[Signature('discord:analyze-profiles')]
class AnalyzeDiscordProfiles extends Command
{
    public function handle(): void
    {
        intro('Discord Profiles — Social Connections Report');

        // Load all chunk files
        $files = collect(Storage::disk('local')->files('discord'))
            ->filter(fn (string $f) => str_contains($f, 'profiles_chunk_'))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            warning('No profile chunks found. Run `php artisan discord:fetch-profiles` first.');

            return;
        }

        info(sprintf('Found %d chunk file(s)', $files->count()));

        $totalProfiles = 0;
        $withConnections = 0;
        $socialCounts = [];
        $githubUsers = [];
        $topConnectors = [];

        foreach ($files as $file) {
            $profiles = json_decode((string) Storage::disk('local')->get($file), true) ?? [];

            foreach ($profiles as $discordId => $profile) {
                $totalProfiles++;

                $accounts = $profile['connected_accounts'] ?? [];
                $username = $profile['user']['username'] ?? '?';
                $globalName = $profile['user']['global_name'] ?? $username;

                if (filled($accounts)) {
                    $withConnections++;
                }

                $userSocials = [];

                foreach ($accounts as $account) {
                    $type = $account['type'];
                    $socialCounts[$type] = ($socialCounts[$type] ?? 0) + 1;
                    $userSocials[] = $type;

                    if ($type === 'github') {
                        $githubUsers[] = [
                            'discord_id' => $discordId,
                            'discord_username' => $username,
                            'github_username' => $account['name'],
                            'github_id' => $account['id'],
                            'verified' => $account['verified'] ?? false,
                        ];
                    }
                }

                if (count($userSocials) >= 3) {
                    $topConnectors[] = [
                        'name' => $globalName,
                        'username' => $username,
                        'count' => count($userSocials),
                        'types' => implode(', ', $userSocials),
                    ];
                }
            }
        }

        arsort($socialCounts);

        // Summary
        $noConnections = $totalProfiles - $withConnections;
        $pctWith = $totalProfiles > 0 ? round($withConnections / $totalProfiles * 100, 1) : 0;

        table(
            headers: ['Metric', 'Count', '%'],
            rows: [
                ['Total profiles', (string) $totalProfiles, '100%'],
                ['With connections', (string) $withConnections, $pctWith.'%'],
                ['No connections', (string) $noConnections, round(100 - $pctWith, 1).'%'],
            ],
        );

        // Social platform breakdown
        $socialRows = [];
        foreach ($socialCounts as $type => $count) {
            $pct = round($count / $totalProfiles * 100, 1);
            $socialRows[] = [(string) $type, (string) $count, $pct.'%'];
        }

        info('Connected accounts by platform');

        table(
            headers: ['Platform', 'Count', '% of profiles'],
            rows: $socialRows,
        );

        // GitHub stats
        $verifiedGh = collect($githubUsers)->where('verified', true)->count();
        info(sprintf('GitHub: %d verified out of ', $verifiedGh).count($githubUsers).' total');

        // Top connectors (3+ socials)
        if (filled($topConnectors)) {
            usort($topConnectors, fn (array $a, array $b) => $b['count'] <=> $a['count']);
            $topConnectors = array_slice($topConnectors, 0, 15);

            info('Top connectors (3+ linked accounts)');

            table(
                headers: ['Name', 'Username', '#', 'Platforms'],
                rows: array_map(
                    static fn (array $c): array => [(string) $c['name'], (string) $c['username'], (string) $c['count'], $c['types']],
                    $topConnectors,
                ),
            );
        }

        // Save GitHub users list
        Storage::disk('local')->put(
            'discord/github_connections.json',
            json_encode([
                'analyzed_at' => now()->toISOString(),
                'total_profiles' => $totalProfiles,
                'total_with_github' => count($githubUsers),
                'connections' => $githubUsers,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        info('GitHub connections saved to storage/app/private/discord/github_connections.json');

        // Save full social report
        Storage::disk('local')->put(
            'discord/social_report.json',
            json_encode([
                'analyzed_at' => now()->toISOString(),
                'total_profiles' => $totalProfiles,
                'with_connections' => $withConnections,
                'platforms' => $socialCounts,
                'github_users' => count($githubUsers),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        outro(sprintf('Report complete — %d profiles analyzed', $totalProfiles));
    }
}
