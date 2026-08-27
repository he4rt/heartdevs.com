<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

#[Description(description: 'Fetch all guild members from Discord API and save to JSON')]
#[Signature(signature: 'discord:fetch-members {--limit=0 : Max members to fetch (0 = all)}')]
class FetchDiscordMembers extends Command
{
    public function handle(): void
    {
        $token = config('he4rt.discord.token');
        $guildId = config('he4rt.discord.guild_id');

        if (!$token) {
            $this->error('Discord bot token not configured (HE4RT_DISCORD_BOT_KEY).');

            return;
        }

        // Step 1: Get guild info to know total member count
        $this->info(sprintf('Fetching guild info for %s...', $guildId));

        $guildResult = $this->discordGet($token, '/guilds/'.$guildId, ['with_counts' => 'true']);
        if ($guildResult['error'] ?? false) {
            return;
        }

        $guild = $guildResult['data'];
        $totalMembers = $guild['approximate_member_count'] ?? 0;
        $this->info(sprintf('Guild: %s — ~%s members (approximate)', $guild['name'], $totalMembers));

        // Step 2: Paginate through all members
        $limit = (int) $this->option('limit');
        $target = $limit > 0 ? min($limit, $totalMembers) : $totalMembers;

        $this->info(sprintf('Target: %s members to fetch...', $target));

        $after = '0';
        $members = [];
        $botCount = 0;

        do {
            $result = $this->discordGet($token, sprintf('/guilds/%s/members', $guildId), [
                'limit' => 1_000,
                'after' => $after,
            ]);

            if ($result['error'] ?? false) {
                if ($members !== []) {
                    $this->warn('Saving partial results before exiting...');
                    break;
                }

                return;
            }

            $batch = $result['data'];

            if (blank($batch)) {
                break;
            }

            foreach ($batch as $member) {
                if ($member['user']['bot'] ?? false) {
                    $botCount++;

                    continue;
                }

                // Dump the full member payload from Discord API
                $members[] = $member;

                if ($limit > 0 && count($members) >= $limit) {
                    break 2;
                }
            }

            $after = end($batch)['user']['id'];
            $this->info('Fetched '.count($members).sprintf(' / ~%s members (skipped %d bots)...', $target, $botCount));
            Sleep::sleep(10);
        } while (count($members) < $target);

        // Step 3: Save to storage
        $output = [
            'scraped_at' => now()->toISOString(),
            'guild_id' => $guildId,
            'guild_name' => $guild['name'] ?? null,
            'approximate_member_count' => $totalMembers,
            'bots_skipped' => $botCount,
            'total_members' => count($members),
            'members' => $members,
        ];

        Storage::disk('local')->put('discord/members.json', json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->info('Done! Saved '.count($members).sprintf(' members to storage/app/private/discord/members.json (skipped %d bots)', $botCount));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    private function discordGet(string $token, string $endpoint, array $query = []): array
    {
        retry:
        $response = Http::withHeaders([
            'Authorization' => 'Bot '.$token,
        ])->get('https://discord.com/api/v10'.$endpoint, $query);

        if ($response->status() === 429) {
            $retryAfter = $response->json('retry_after', 5);
            $this->warn(sprintf('Rate limited. Waiting %ss...', $retryAfter));
            Sleep::sleep((int) ceil($retryAfter));
            goto retry;
        }

        if ($response->status() === 403) {
            $this->error('403 Forbidden — Missing Access.');
            $this->error('Make sure the GUILD_MEMBERS privileged intent is enabled:');
            $this->error('  Discord Developer Portal → Your App → Bot → Privileged Gateway Intents → Server Members Intent');
            $this->error('Also verify the bot is a member of the guild and has proper permissions.');

            return ['error' => true];
        }

        if ($response->failed()) {
            $this->error(sprintf('API request failed [%s]: %s', $response->status(), $response->body()));

            return ['error' => true];
        }

        return ['data' => $response->json()];
    }
}
