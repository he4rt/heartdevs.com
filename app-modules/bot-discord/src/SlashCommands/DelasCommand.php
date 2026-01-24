<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Illuminate\Support\Facades\Log;
use Laracord\Commands\SlashCommand;
use Throwable;

class DelasCommand extends SlashCommand
{
    protected $name = 'delas';

    protected $description = 'Adiciona role He4rt Delas a um membro da comunidade';

    protected $options = [
        [
            'name' => 'user',
            'description' => 'Usuário para receber a role He4rt Delas',
            'type' => Option::USER,
            'required' => true,
        ],
    ];

    protected $permissions = [];

    protected $admin = false;

    protected $hidden = false;

    private readonly string $presentationRoleId;

    private readonly string $he4rtDelasRoleId;

    public function __construct()
    {
        $this->presentationRoleId = config('bot-discord.roles.presentation');
        $this->he4rtDelasRoleId = config('bot-discord.roles.technologies.he4rt_delas');
    }

    public function handle(Interaction $interaction): void
    {
        try {
            $targetUserId = $this->value('user');

            if (! $targetUserId) {
                $interaction->respondWithMessage('❌ Usuário não especificado.', true);

                return;
            }

            if (! $this->validateExecutor($interaction)) {
                return;
            }

            $targetMember = $this->validateTarget($interaction, $targetUserId);
            if (! $targetMember) {
                return;
            }

            // Verificar se não é ele mesmo
            if ($interaction->user->id === $targetUserId) {
                $interaction->respondWithMessage('❌ Você não pode usar este comando em você mesmo.', true);

                return;
            }

            $this->addHe4rtDelasRole($interaction, $targetMember, $targetUserId);

        } catch (Throwable $throwable) {
            Log::error('Error in MulherCommand', [
                'executor_id' => $interaction->user->id,
                'target_id' => $targetUserId ?? 'unknown',
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            $interaction->respondWithMessage('❌ Erro ao processar comando. Tente novamente.', true);
        }
    }

    private function validateExecutor(Interaction $interaction): bool
    {

        $hasPresentation = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === $this->presentationRoleId);

        if (! $hasPresentation) {
            $interaction->respondWithMessage('❌ Você precisa se apresentar primeiro para usar este comando (/apresentar).',
                true);

            return false;
        }

        $hasHe4rtDelas = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === $this->he4rtDelasRoleId);

        if (! $hasHe4rtDelas) {
            $interaction->respondWithMessage('❌ Você não tem permissão para usar este comando.', true);

            return false;
        }

        return true;
    }

    private function validateTarget(Interaction $interaction, string $targetUserId)
    {
        $targetMember = $interaction->guild->members->get('id', $targetUserId);

        if (! $targetMember) {
            $interaction->respondWithMessage('❌ Usuário mencionado não foi encontrado no servidor.', true);

            return null;
        }

        if ($targetMember->user->bot ?? false) {
            $interaction->respondWithMessage('❌ Não é possível adicionar roles a bots.', true);

            return null;
        }

        $targetHasPresentation = $targetMember->roles
            ->find(fn (Role $role) => $role->id === $this->presentationRoleId);

        if (! $targetHasPresentation) {
            $username = $targetMember->user->username ?? 'usuário';
            $interaction->respondWithMessage(sprintf('❌ @%s precisa se apresentar primeiro (/apresentar).', $username), true);

            return null;
        }

        $targetHasHe4rtDelas = $targetMember->roles
            ->find(fn (Role $role) => $role->id === $this->he4rtDelasRoleId);

        if ($targetHasHe4rtDelas) {
            $username = $targetMember->user->username ?? 'usuário';
            $interaction->respondWithMessage(sprintf('❌ @%s já possui a role He4rt Delas.', $username), true);

            return null;
        }

        return $targetMember;
    }

    private function addHe4rtDelasRole(Interaction $interaction, $targetMember, string $targetUserId): void
    {
        $currentRoles = [];
        foreach ($targetMember->roles as $role) {
            $currentRoles[] = $role->id;
        }

        $currentRoles[] = $this->he4rtDelasRoleId;

        $currentRoles = array_unique($currentRoles);

        $targetMember->setRoles($currentRoles);

        $interaction->respondWithMessage(
            "**Role He4rt Delas adicionada com sucesso!**\n\n"
            .sprintf('<@%s> agora faz parte do He4rt Delas, adicionada por <@%s>!', $targetUserId, $interaction->user->id)
        );
    }
}
