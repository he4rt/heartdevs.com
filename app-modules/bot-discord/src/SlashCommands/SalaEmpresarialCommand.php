<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Exception;
use He4rt\BotDiscord\Actions\VoiceChannel\ConfigureEmpresarialRoomAction;
use He4rt\BotDiscord\DTO\EmpresarialOverwritePlan;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use React\Promise\PromiseInterface;

use function React\Async\await;

class SalaEmpresarialCommand extends AbstractSlashCommand
{
    protected $name = 'sala-empresarial';

    protected $description = 'Transforma a sala de voz atual em uma Sala Empresarial privada da empresa parceira.';

    protected $admin = false;

    protected $hidden = false;

    public function handle(Interaction $interaction): void
    {
        $userId = $interaction->member->id;
        $channelId = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$userId);

        /** @var list<VoiceChannelDTO> $activeChannels */
        $activeChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        $decision = resolve(ConfigureEmpresarialRoomAction::class)->execute(
            companySlug: (string) $this->value('empresa'),
            callerRoleIds: $this->callerRoleIds($interaction),
            currentChannelId: $channelId ? (string) $channelId : null,
            activeChannels: $activeChannels,
        );

        if (!$decision->isApproved()) {
            $this->message()
                ->content($decision->rejection->message())
                ->reply($interaction, ephemeral: true);

            return;
        }

        $this->applyOverwrites($interaction, (string) $channelId, $decision->plan);
    }

    /**
     * @return array<mixed>
     */
    public function options(): array
    {
        return [
            [
                'name' => 'empresa',
                'description' => 'Empresa parceira para a qual a sala ficará privada.',
                'type' => Option::STRING,
                'required' => true,
                'choices' => $this->companyChoices(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function callerRoleIds(Interaction $interaction): array
    {
        $roleIds = [];

        foreach ($interaction->member->roles as $role) {
            /** @var Role $role */
            $roleIds[] = $role->id;
        }

        return $roleIds;
    }

    private function applyOverwrites(Interaction $interaction, string $channelId, EmpresarialOverwritePlan $plan): void
    {
        /** @var Channel|null $voiceChannel */
        $voiceChannel = $interaction->guild->channels->get('id', $channelId);

        /** @var Role|null $partnerRole */
        $partnerRole = $interaction->guild->roles->get('id', $plan->partnerRoleId);

        // The `@everyone` role shares the guild id in Discord.
        /** @var Role|null $everyoneRole */
        $everyoneRole = $interaction->guild->roles->get('id', $interaction->guild->id);

        if (!$voiceChannel || !$partnerRole || !$everyoneRole) {
            $this->message()
                ->content('❌ Não foi possível configurar a sala. Tente novamente.')
                ->reply($interaction, ephemeral: true);

            return;
        }

        try {
            /** @var PromiseInterface<Channel> $denyEveryone */
            $denyEveryone = $voiceChannel->setPermissions($everyoneRole, [], $plan->denyEveryone);
            await($denyEveryone);

            /** @var PromiseInterface<Channel> $allowPartner */
            $allowPartner = $voiceChannel->setPermissions($partnerRole, $plan->allowPartnerRole, []);
            await($allowPartner);

            $this->message()
                ->content(sprintf('✅ Sala configurada como privada para a empresa **%s**!', mb_strtoupper($plan->companySlug)))
                ->reply($interaction, ephemeral: true);
        } catch (Exception) {
            $this->message()
                ->content('❌ Erro ao configurar a sala. Tente novamente.')
                ->reply($interaction, ephemeral: true);
        }
    }

    /**
     * @return list<array{name: string, value: string}>
     */
    private function companyChoices(): array
    {
        /** @var array<string, string> $partners */
        $partners = config('bot-discord.roles.partners', []);

        return array_map(
            fn (string $slug): array => ['name' => mb_strtoupper($slug), 'value' => $slug],
            array_keys($partners),
        );
    }
}
