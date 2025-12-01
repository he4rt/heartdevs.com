<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;

class GreetingsEvent extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_CREATE;

    /**
     * Handle the event.
     */
    public function handle(Message $message, Discord $discord): void
    {
        if ($message->author->bot) {
            return;
        }

        $allowedChannel = config('services.discord.general_chat_id');

        $this->logger()->notice(sprintf('%s: %s', $message->author->username, $message->content));

        $hour = now()->hour;
        $payload = mb_strtolower($message->content);

        $response = match (true) {
            $hour >= 00 && $hour < 5 && str_contains($payload, 'salve') => 'boa pa nois',
            $hour >= 5 && $hour < 12 && str_contains($payload, 'bom dia') => 'Bom dia!',
            $hour >= 12 && $hour < 18 && str_contains($payload, 'boa tarde') => 'Boa tarde!',
            $hour >= 18 && str_contains($payload, 'boa noite') => 'Boa noite!',
            default => null
        };

        if (is_null($response)) {
            return;
        }

        $message->reply($response);
    }
}
