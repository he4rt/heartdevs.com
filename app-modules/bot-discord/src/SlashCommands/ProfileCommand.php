<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Interaction;
use He4rt\Provider\Models\Provider;
use Throwable;

class ProfileCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'profile';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Profile Command slash command.';

    /**
     * The command options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The permissions required to use the command.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Indicates whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Indicates whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the slash command.
     */
    public function handle(Interaction $interaction): void
    {
        try {

            if (! $this->memberProvider instanceof Provider || ! $this->memberProvider->user->information) {
                $this
                    ->message()
                    ->content('Você ainda não se apresentou! Use o comando `/introduction` primeiro.')
                    ->reply($interaction, true);

                return;
            }

            $information = $this->memberProvider->user->information;

            $this
                ->message()
                ->content('https://heartdevs.com/')
                ->color('800080')
                ->title('Perfil de '.($information->nickname ?? '-'))
                ->thumbnailUrl($interaction->user->avatar)
                ->fields([
                    'Nome/Nickname' => $information->nickname ?? '-',
                    'Sobre' => $information->about ?? '-',
                ])
                ->fields([
                    'Git/Github' => $information->github_url ?? '-',
                    'Linkedin' => $information->linkedin_url ?? '-',
                ], inline: false)
                ->footerIcon($interaction->guild->icon)
                ->footerText('HE4RT INC')
                ->timestamp(now())
                ->reply($interaction, true);
        } catch (Throwable $throwable) {
            $this->logger()->error('Erro ao buscar perfil: '.$throwable->getMessage());

            $this
                ->message()
                ->content('Erro ao buscar perfil.')
                ->reply($interaction, true);
        }
    }
}
