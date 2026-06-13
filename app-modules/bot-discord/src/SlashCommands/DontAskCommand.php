<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Facades\Date;

class DontAskCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'perguntar';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Não peça para perguntar. Mande direto!';

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
     * @var array<mixed>
     */
    protected $options = [
        [
            'name' => 'user',
            'description' => 'Mencione um usuário para citar.',
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
            ->thumbnailUrl($interaction->data->resolved->users->get('id', $this->value('user'))->avatar)
            ->footerText(Date::now()->format('Y').' © He4rt Developers')
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
