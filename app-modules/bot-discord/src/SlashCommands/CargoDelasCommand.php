<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Member;

class CargoDelasCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'cargo-delas';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Adiciona a tag He4rt Delas a um membro que já se apresentou.';

    /**
     * The command options.
     *
     * @var array<mixed>
     */
    protected $options = [
        [
            'name' => 'user',
            'description' => 'Mencione a usuária para adicionar a tag.',
            'type' => Option::USER,
            'required' => true,
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
        $comiteRoleId = config('bot-discord.roles.comite_delas');

        $hasComiteRole = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === $comiteRoleId);

        if (!$hasComiteRole) {
            $interaction->respondWithMessage('❌ Você não tem permissão para usar este comando.', true);

            return;
        }

        $targetUserId = $this->value('user');
        $targetMember = $this->validateTarget($interaction, $targetUserId);

        if (!$targetMember instanceof Member) {
            return;
        }

        $roles = [];
        foreach ($targetMember->roles as $role) {
            $roles[] = $role->id;
        }

        $he4rtDelasRoleId = config('bot-discord.roles.he4rt_delas');
        $roles[] = $he4rtDelasRoleId;

        $targetMember->setRoles($roles);

        $interaction->respondWithMessage(':he4rtDelas: Cargo adicionado com sucesso!', true);

        $delasChannelId = config('bot-discord.channels.he4rt_delas');
        $welcomeMessage = sprintf(
            '<@%s>, boas-vindas. Se precisar de ajuda, é só chamar :he4rtDelas:',
            $targetUserId
        );

        $this->message()
            ->content($welcomeMessage)
            ->send($delasChannelId);
    }

    private function validateTarget(Interaction $interaction, string $targetUserId): ?Member
    {
        /** @var Member|null $targetMember */
        $targetMember = $interaction->guild->members->get('id', $targetUserId);

        if (!$targetMember) {
            $interaction->respondWithMessage('❌ Usuário mencionado não foi encontrado no servidor.', true);

            return null;
        }

        if ($targetMember->user->bot ?? false) {
            $interaction->respondWithMessage('❌ Não é possível adicionar roles a bots.', true);

            return null;
        }

        $presentationRoleId = config('bot-discord.roles.presentation');
        $targetHasPresentation = $targetMember->roles
            ->find(fn (Role $role) => $role->id === $presentationRoleId);

        if (!$targetHasPresentation) {
            $username = $targetMember->user->username ?? 'usuário';
            $interaction->respondWithMessage(sprintf('❌ @%s precisa se apresentar primeiro (/apresentar).', $username), true);

            return null;
        }

        $he4rtDelasRoleId = config('bot-discord.roles.he4rt_delas');
        $targetHasHe4rtDelas = $targetMember->roles
            ->find(fn (Role $role) => $role->id === $he4rtDelasRoleId);

        if ($targetHasHe4rtDelas) {
            $username = $targetMember->user->username ?? 'usuário';
            $interaction->respondWithMessage(sprintf('❌ @%s já possui a role He4rt Delas.', $username), true);

            return null;
        }

        return $targetMember;
    }
}
