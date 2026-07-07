<?php

declare(strict_types=1);

namespace App\Console\Commands;

use He4rt\Identity\ExternalIdentity\Actions\CreateAccountByExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Description('Import Discord members and GitHub connections from JSON files into the database')]
#[Signature('discord:import-members
        {--members-file=discord/members.json : Path to members JSON (relative to storage/app/private)}
        {--github-file=discord/github_connections.json : Path to GitHub connections JSON}
        {--dry-run : Show what would be imported without writing}
        {--force : Overwrite existing github_url values}')]
class ImportDiscordMembers extends Command
{
    public function handle(CreateAccountByExternalIdentity $createAccount): void
    {
        $membersFile = $this->option('members-file');
        $githubFile = $this->option('github-file');

        if (!Storage::disk('local')->exists($membersFile)) {
            $this->error('Members file not found: storage/app/private/'.$membersFile);
            $this->info('Run `php artisan discord:fetch-members` first.');

            return;
        }

        $membersData = json_decode((string) Storage::disk('local')->get($membersFile), associative: true);
        $members = $membersData['members'] ?? [];

        $this->info('Loaded '.count($members).sprintf(' members from %s (scraped: %s)', $membersFile, $membersData['scraped_at']));

        // Load GitHub connections if available
        $githubMap = [];

        if (Storage::disk('local')->exists($githubFile)) {
            $githubData = json_decode((string) Storage::disk('local')->get($githubFile), associative: true);
            foreach ($githubData['connections'] ?? [] as $conn) {
                $githubMap[$conn['discord_id']] = $conn['github_username'];
            }

            $this->info('Loaded '.count($githubMap).(' GitHub connections from '.$githubFile));
        } else {
            $this->warn(sprintf('GitHub file not found (%s), skipping GitHub imports.', $githubFile));
        }

        $tenantId = (string) config('he4rt.tenant_id');

        if ($tenantId === '') {
            $this->error('No tenant configured (set HE4RT_TENANT_ID).');

            return;
        }

        $this->info(sprintf('Importing into tenant: %s', $tenantId));

        $stats = ['created' => 0, 'existing' => 0, 'github_set' => 0, 'github_skipped' => 0];
        $csvRows = [];
        $isDryRun = $this->option('dry-run');
        $this->option('force');

        $bar = $this->output->createProgressBar(count($members));
        $bar->start();

        foreach ($members as $member) {
            $discordId = $member['discord_id'];
            $username = $member['global_name'] ?? $member['username'];

            // Check if ExternalIdentity already exists
            $existing = ExternalIdentity::query()
                ->where('provider', IdentityProvider::Discord->value)
                ->where('provider_id', $discordId)
                ->first();

            $user = null;

            if ($existing) {
                $stats['existing']++;
                $user = $existing->user;

                // Update avatar and username on provider
                if (!$isDryRun) {
                    $existing->update([
                        'username' => $member['username'],
                        'avatar' => $member['avatar_url'],
                    ]);
                }
            } else {
                $stats['created']++;

                if (!$isDryRun) {
                    $identity = $createAccount->handle(
                        tenantId: $tenantId,
                        provider: IdentityProvider::Discord,
                        providerId: $discordId,
                        username: $username,
                    );
                    $user = $identity->user;
                }
            }

            // GitHub URL import removed — legacy information() model no longer exists.
            // GitHub is now derived from ExternalIdentity (OAuth) connections.

            $csvRows[] = [
                'discord_id' => $discordId,
                'username' => $member['username'],
                'global_name' => $member['global_name'] ?? '',
                'status' => $existing ? 'existing' : 'created',
                'github_username' => $githubMap[$discordId] ?? '',
                'joined_at' => $member['joined_at'],
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Generate CSV
        if (!$isDryRun) {
            $csvContent = implode(',', array_keys($csvRows[0] ?? []))."\n";
            foreach ($csvRows as $row) {
                $csvContent .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row))."\n";
            }

            Storage::disk('local')->put('discord/members_merged.csv', $csvContent);
            $this->info('CSV saved to storage/app/private/discord/members_merged.csv');
        }

        // Summary
        $prefix = $isDryRun ? '[DRY RUN] ' : '';
        $this->table(
            ['Metric', 'Count'],
            [
                [$prefix.'Total members processed', count($members)],
                [$prefix.'New users created', $stats['created']],
                [$prefix.'Existing users found', $stats['existing']],
                [$prefix.'GitHub URLs set', $stats['github_set']],
                [$prefix.'GitHub URLs skipped (existing)', $stats['github_skipped']],
            ]
        );
    }
}
