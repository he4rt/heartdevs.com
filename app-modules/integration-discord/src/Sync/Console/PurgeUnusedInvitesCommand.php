<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Console;

use He4rt\IntegrationDiscord\Sync\Actions\PurgeUnusedInvitesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Purge unused infinite Discord guild invites (max_age=0, uses=0)')]
#[Signature('discord:purge-invites {guild_id?} {--dry-run : List invites without deleting} {--include-expiring : Also purge unused invites that have an expiration time}')]
final class PurgeUnusedInvitesCommand extends Command
{
    public function handle(PurgeUnusedInvitesAction $action): int
    {
        $guildId = $this->argument('guild_id') ?? config('he4rt.discord.guild_id');

        if ($guildId === null) {
            $this->error('No guild ID provided and no default configured.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $includeExpiring = (bool) $this->option('include-expiring');

        $scope = $includeExpiring ? 'unused' : 'unused infinite';

        $this->info(sprintf(
            '%s %s invites for guild %s...',
            $dryRun ? 'Scanning' : 'Purging',
            $scope,
            $guildId,
        ));

        $result = $action->execute((string) $guildId, $dryRun, $includeExpiring);

        if ($result['matched'] === 0) {
            $this->info(sprintf('No %s invites found. Nothing to do.', $scope));

            return self::SUCCESS;
        }

        $this->table(
            ['Code', 'Inviter', 'Channel', 'Created At'],
            array_map(
                static fn (array $invite): array => [
                    $invite['code'],
                    $invite['inviter'],
                    $invite['channel'],
                    $invite['created_at'],
                ],
                $result['invites'],
            ),
        );

        $this->newLine();
        $this->info(sprintf(
            'Found %d %s invite(s) out of %d total.',
            $result['matched'],
            $scope,
            $result['total'],
        ));

        if ($dryRun) {
            $this->warn('DRY RUN -- no invites were deleted.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Deleted %d invite(s).', $result['deleted']));

        if ($result['failed'] > 0) {
            $this->warn(sprintf('%d invite(s) failed to delete. Check logs for details.', $result['failed']));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
