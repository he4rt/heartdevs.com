<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Console;

use He4rt\IntegrationDiscord\Sync\Actions\SyncDiscordGuildAction;
use Illuminate\Console\Command;

final class SyncDiscordGuildCommand extends Command
{
    protected $signature = 'discord:sync {guild_id?} {--fresh : Truncate guild data before re-importing}';

    protected $description = 'Sync Discord guild data (channels, roles, members) from the Discord API';

    public function handle(SyncDiscordGuildAction $action): int
    {
        $guildId = $this->argument('guild_id') ?? config('he4rt.discord.guild_id');

        if ($guildId === null) {
            $this->error('No guild ID provided and no default configured.');

            return self::FAILURE;
        }

        $fresh = (bool) $this->option('fresh');

        $this->info(sprintf('Syncing Discord guild %s%s...', $guildId, $fresh ? ' (fresh)' : ''));

        $guild = $action->execute((string) $guildId, $fresh);

        $this->info(sprintf(
            'Sync complete: %s — %d channels, %d roles, %d members',
            $guild->name,
            $guild->channels()->count(),
            $guild->roles()->count(),
            $guild->members()->count(),
        ));

        return self::SUCCESS;
    }
}
