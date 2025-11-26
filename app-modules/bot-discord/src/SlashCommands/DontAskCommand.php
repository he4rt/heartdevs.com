<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\SlashCommand;

class DontAskCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'dont-ask';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Dont Ask Command command.';

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
     * The slash command options.
     *
     * @var array
     */
    protected $options = [
        [
            'name' => 'user',
            'description' => 'Mention a user to be quoted.',
            'type' => Option::USER,
            'required' => true,
        ],
    ];

    /**
     * Handle the command.
     */
    public function handle(Interaction $interaction): void
    {
        $this
            ->message()
            ->title('Não peça para perguntar.')
            ->footerIcon($interaction->guild->icon)
            ->thumbnailUrl($interaction->user->avatar)
            ->footerText('HE4RT INC')
            ->imageUrl('https://media.discordapp.net/attachments/546151895010508827/1046092564513701909/Frame_1282_1.png')
            ->timestamp(now())
            ->content("Ei <@{$this->value('user')}>
Explique a ideia
Mostre o que você tentou
Mostre o que deu errado
E nos facilite a resolver o seu problema!")
            ->reply($interaction);
    }
}
