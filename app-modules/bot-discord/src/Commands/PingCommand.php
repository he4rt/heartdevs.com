<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Commands;

use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class PingCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'ping';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Ping Command command.';

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the command.
     *
     * @param  array<mixed>  $args
     */
    public function handle(Message $message, array $args): void
    {
        $this
            ->message()
            ->title('Ping Command')
            ->content('Hello world!')
            ->button('👋', route: 'wave')
            ->reply($message);
    }

    /**
     * The command interaction routes.
     *
     * @return array<mixed>
     */
    public function interactions(): array
    {
        return [
            'wave' => fn (Interaction $interaction) => $this->message('👋')->reply($interaction),
        ];
    }
}
