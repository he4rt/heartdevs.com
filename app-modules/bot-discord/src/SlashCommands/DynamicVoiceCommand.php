<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\ChannelBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use Laracord\Commands\SlashCommand;

use function React\Async\await;

class DynamicVoiceCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'sala';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Dynamic Voice Command slash command.';

    /**
     * The command options.
     *
     * @var array<mixed>
     */
    protected $options = [];

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
    protected $hidden = true;

    /**
     * Handle the slash command.
     */
    public function handle(Interaction $interaction): void
    {
        $channel = await($interaction->guild->channels->build(
            $interaction->guild,
            ChannelBuilder::new($this->value('tipo'))
                ->setType(Channel::TYPE_GUILD_VOICE)
                ->setUserLimit($this->value('quantidade'))
                ->setParentId(config('he4rt.channels.dynamic_voice_category')),
        ));
        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        $channelDto = VoiceChannelDTO::make([
            'guildId' => $interaction->guild->id,
            'channelId' => $channel->id,
            'ownerId' => $interaction->user->id,
            'usersCount' => 0,
            'users' => [],
            'lastJoinedAt' => now(),
        ]);
        $channels[] = $this->dtoToArray($channelDto);

        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $channels);

        await($interaction->guild->channels->freshen());

        $this->interactionWithUser($interaction, $channel);
    }

    /**
     * @return array<mixed>
     */
    public function options(): array
    {
        return [
            [
                'name' => 'tipo',
                'description' => 'Manage the ticket system.',
                'type' => Option::STRING,
                'required' => true,
                'choices' => $this->getVoiceChoices(),
            ],
            [
                'name' => 'quantidade',
                'description' => 'Manage how many people can use the voice channel.',
                'type' => Option::INTEGER,
                'required' => true,
                'max_value' => 99,
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getVoiceChoices(): array
    {
        $items = [
            ['name' => '🗣 Only English'],
            ['name' => '👥 Novas Amizades'],
            ['name' => '👋 Novato'],
            ['name' => '🎓 Mentoria'],
            ['name' => '🏢 Trabalho'],
            ['name' => '📖 Estudando'],
            ['name' => '🔴 Live'],
            ['name' => '🎮 Joguinhos'],
            ['name' => '🗣 Conversando'],
            ['name' => '🆘 ME AJUDAAA!!!!'],
        ];

        return array_map(fn (array $item) => ['name' => $item['name'], 'value' => str($item['name'])->toString()], $items);
    }

    private function interactionWithUser(Interaction $interaction, Channel $channel): void
    {

        $embed = new Embed($this->discord());
        $embed->setTitle('Canal de Voz')
            ->setDescription(sprintf('Link do canal de voz: <#%s>', $channel->id));

        $channel->sendMessage(message: MessageBuilder::new()
            ->setContent(sprintf('<@%s>', $interaction->user->id))
            ->addEmbed($embed));

        $this->message()
            ->content(sprintf('Sala Criada com sucesso !! <#%s>', $channel->id))
            ->reply($interaction, true);
    }

    private function dtoToArray(VoiceChannelDTO $dto): array
    {
        return [
            'guildId' => $dto->guildId,
            'channelId' => $dto->channelId,
            'ownerId' => $dto->ownerId,
            'usersCount' => $dto->usersCount,
            'users' => $dto->users,
            'lastJoinedAt' => $dto->lastJoinedAt?->toIso8601String(),
        ];
    }
}
