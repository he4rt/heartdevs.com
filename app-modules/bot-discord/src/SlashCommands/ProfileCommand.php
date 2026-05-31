<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\Date;
use Throwable;

class ProfileCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'perfil';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Exiba seu perfil ou de outro membro.';

    /**
     * The command options.
     *
     * @var array<mixed>
     */
    protected $options = [
        [
            'name' => 'user',
            'description' => 'Mencione um usuário para verificar seu perfil.',
            'type' => Option::USER,
            'required' => false,
        ],
    ];

    /**
     * The permissions required to use the command.
     *
     * @var array<mixed>
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
        $mentionedUser = $interaction->user;

        if ($userId = $this->value('user')) {
            $this->memberProvider = $this->getMemberProviderQuery()->where('external_account_id', $userId)->first();
            $mentionedUser = $interaction->data->resolved->users->get('id', $userId);
        }

        try {

            if (!$this->memberProvider instanceof ExternalIdentity) {
                $this
                    ->message()
                    ->content($mentionedUser.' ainda não se apresentou! Use o comando `/apresentar` primeiro.')
                    ->reply($interaction, true);

                return;
            }

            $profile = Profile::query()
                ->where('user_id', $this->memberProvider->user->id)
                ->where('tenant_id', $this->memberProvider->tenant_id)
                ->first();

            if (!$profile) {
                $this
                    ->message()
                    ->content($mentionedUser.' ainda não possui um perfil.')
                    ->reply($interaction, true);

                return;
            }

            $this
                ->message()
                ->content('https://heartdevs.com/')
                ->color('800080')
                ->title('Perfil de '.($profile->nickname ?? $this->memberProvider->user->name))
                ->thumbnailUrl($mentionedUser->avatar)
                ->fields([
                    'Nome/Nickname' => $profile->nickname ?? '-',
                    'Sobre' => $profile->about ?? '-',
                ])
                ->footerIcon($interaction->guild->icon)
                ->footerText(Date::now()->format('Y').' © He4rt Developers')
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
