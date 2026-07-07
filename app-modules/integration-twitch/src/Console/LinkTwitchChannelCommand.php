<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\IntegrationTwitch\Transport\Requests\Users\GetUsers;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Resolve a Twitch channel broadcaster id for the fixed config-based integration')]
#[Signature('twitch:link-channel {login? : Twitch channel login name (defaults to config broadcaster login)}')]
final class LinkTwitchChannelCommand extends Command
{
    public function handle(TwitchHelixConnector $helix): int
    {
        $login = $this->argument('login') ?? config()->string('services.twitch.broadcaster_login', '');

        if ($login === '') {
            $this->error('No Twitch login provided. Pass a login argument or set TWITCH_BROADCASTER_LOGIN.');

            return self::FAILURE;
        }

        $response = $helix->send(new GetUsers(login: $login));
        $users = $response->json('data', []);

        if (blank($users)) {
            $this->error(sprintf("Twitch user '%s' not found.", $login));

            return self::FAILURE;
        }

        $twitchUser = $users[0];
        $broadcasterId = $twitchUser['id'];
        $displayName = $twitchUser['display_name'];

        $this->info(sprintf("Resolved Twitch channel '%s' (login: %s).", $displayName, $login));
        $this->line(sprintf('Broadcaster ID: %s', $broadcasterId));
        $this->newLine();
        $this->comment('Set the following in your .env to activate the integration:');
        $this->line(sprintf('  TWITCH_BROADCASTER_LOGIN=%s', $login));
        $this->line(sprintf('  TWITCH_BROADCASTER_ID=%s', $broadcasterId));

        return self::SUCCESS;
    }
}
