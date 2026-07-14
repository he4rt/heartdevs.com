<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\IntegrationTwitch\Transport\Requests\Users\GetUsers;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description(description: 'Resolve a Twitch channel broadcaster id for use with twitch:subscribe')]
#[Signature(signature: 'twitch:link-channel {login? : Twitch channel login name (defaults to config broadcaster login)}')]
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
        $this->comment('Register EventSub subscriptions for this channel with:');
        $this->line(sprintf('  php artisan twitch:subscribe %s --all', $broadcasterId));

        return self::SUCCESS;
    }
}
