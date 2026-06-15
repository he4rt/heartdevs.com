<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Exception;
use React\Promise\PromiseInterface;

use function React\Async\await;

class EditVoiceChannelLimitCommand extends AbstractSlashCommand
{
    protected $name = 'sala-limite';

    protected $description = 'Editar o limite máximo de membros na sala de voz atual';

    protected $admin = false;

    protected $hidden = false;

    public function handle(Interaction $interaction): void
    {
        $userId = $interaction->member->id;
        $channelId = cache()->tags(['voice_tracking'])->get('user_last_channel_'.$userId);

        if (!$channelId) {
            $this->message()
                ->content('❌ Você precisa estar em uma sala de voz para usar este comando!')
                ->reply($interaction, true);

            return;
        }

        $activeChannels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);
        $channelDto = collect($activeChannels)->firstWhere('channelId', $channelId);

        if (!$channelDto) {
            $this->message()
                ->content('❌ Este comando só funciona em salas criadas com /sala!')
                ->reply($interaction, true);

            return;
        }

        if ($channelDto->ownerId !== $userId) {
            $this->message()
                ->content('❌ Apenas o dono da sala pode alterar o limite de membros!')
                ->reply($interaction, true);

            return;
        }

        /** @var Channel|null $voiceChannel */
        $voiceChannel = $interaction->guild->channels->get('id', $channelId);

        if (!$voiceChannel) {
            $this->message()
                ->content('❌ Canal de voz não encontrado!')
                ->reply($interaction, true);

            return;
        }

        $newLimit = $this->value('limite');

        try {
            $voiceChannel->user_limit = (int) $newLimit;
            /** @var PromiseInterface<Channel> $promise */
            $promise = $interaction->guild->channels->save($voiceChannel);
            await($promise);

            $this->message()
                ->content(sprintf('✅ Limite da sala atualizado para **%d** membros!', $newLimit))
                ->reply($interaction, true);
        } catch (Exception) {
            $this->message()
                ->content('❌ Erro ao atualizar o limite da sala. Tente novamente.')
                ->reply($interaction, true);
        }
    }

    /**
     * @return array<mixed>
     */
    public function options(): array
    {
        return [
            [
                'name' => 'limite',
                'description' => 'Novo limite máximo de membros (1-99)',
                'type' => Option::INTEGER,
                'required' => true,
                'min_value' => 1,
                'max_value' => 99,
            ],
        ];
    }
}
