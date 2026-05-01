<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchDiscordProfile extends Command
{
    protected $signature = 'discord:fetch-profile
        {user_id : Discord user ID to fetch}
        {--token= : Discord user token (or set DISCORD_USER_TOKEN in .env)}';

    protected $description = 'Fetch a Discord user profile including connected accounts using a user token';

    public function handle(): void
    {
        $userId = $this->argument('user_id');
        $token = $this->option('token');

        if (!is_string($token) || $token === '') {
            $this->error('Provide a token via --token');

            return;
        }

        $guildId = config('he4rt.discord.guild_id');

        $this->info(sprintf('Fetching profile for user %s...', $userId));

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
            $this->error(sprintf('Rate limited. Retry after %ss.', $retryAfter));

            return;
        }

        if ($response->failed()) {
            $this->error(sprintf('Request failed [%s]: %s', $response->status(), $response->body()));

            return;
        }

        $data = $response->json();

        $this->info(sprintf('User: %s (%s)', $data['user']['username'], $data['user']['global_name']));

        $accounts = $data['connected_accounts'] ?? [];
        if (blank($accounts)) {
            $this->warn('No connected accounts found.');
        } else {
            $this->info('Connected accounts:');
            $this->table(
                ['Type', 'Name', 'ID', 'Verified'],
                array_map(fn (array $a) => [$a['type'], $a['name'], $a['id'], $a['verified'] ? 'Yes' : 'No'], $accounts),
            );
        }

        $this->newLine();
        $this->info('Full response:');
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
