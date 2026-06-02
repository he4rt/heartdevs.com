<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Console;

use DateTimeImmutable;
use He4rt\Activity\Message\Actions\NewMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\ListChannelMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Sync historical messages from a Discord channel to ensure Meeting Showcase data is available.')]
#[Signature('discord:sync-history {channel_id} {--limit=100 : Number of messages to fetch}')]
final class SyncDiscordHistoryCommand extends Command
{
    public function handle(NewMessage $newMessageAction): int
    {
        $channelId = (string) $this->argument('channel_id');
        $limit = (int) $this->option('limit');
        
        $botToken = config('discord.token') ?? env('HE4RT_DISCORD_BOT_KEY');
        $guildId = env('HE4RT_DISCORD_GUILD');

        if (! $botToken) {
            $this->error('Discord bot token not configured.');
            return self::FAILURE;
        }

        // Find the tenant associated with this guild to ensure multi-tenancy rules are respected
        $tenantProvider = ExternalIdentity::query()
            ->where('model_type', (new Tenant())->getMorphClass())
            ->where('external_account_id', (string) $guildId)
            ->first();

        if (! $tenantProvider) {
            $this->error("Tenant not found for guild {$guildId}. Check your environment configuration.");
            return self::FAILURE;
        }

        $this->info("Fetching last {$limit} messages from channel {$channelId}...");

        try {
            $connector = new DiscordConnector($botToken);
            $response = $connector->send(new ListChannelMessages($channelId, limit: $limit));

            if (! $response->successful()) {
                $this->error("Failed to fetch messages from Discord: " . $response->body());
                return self::FAILURE;
            }

            $messages = $response->json();
            $count = 0;

            $this->withProgressBar($messages, function (array $m) use ($tenantProvider, $newMessageAction, &$count) {
                if (isset($m['author']['bot']) && $m['author']['bot']) {
                    return;
                }

                $author = $m['author'];

                try {
                    $newMessageAction->persist(new NewMessageDTO(
                        tenantId: $tenantProvider->tenant_id,
                        provider: IdentityProvider::Discord,
                        providerUsername: $author['username'],
                        externalAccountId: $author['id'],
                        providerMessageId: $m['id'],
                        channelId: $m['channel_id'],
                        content: $m['content'] ?? '',
                        sentAt: new DateTimeImmutable($m['timestamp']),
                        avatar: $author['avatar'] ?? null,
                    ));
                    $count++;
                } catch (Throwable $e) {
                    // Silently skip individual message failures to keep progress
                }
            });

            $this->newLine();
            $this->info("Successfully synced {$count} messages and updated participant profiles.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("An error occurred during sync: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
