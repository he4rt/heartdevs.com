<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\ChannelBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
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
    protected $hidden = true;

    /**
     * Handle the slash command.
     */
    public function handle(Interaction $interaction): void
    {
        $channel = await($interaction->guild->channels->build(
            $interaction->guild,
            ChannelBuilder::new($this->value('tipo'))
                ->setType(2)
                ->setUserLimit($this->value('quantidade'))
                ->setParentId('1447692330235859104') // TODO: change to "use/sala" category id
        ));
        $channels = cache()->tags(['voice_channels'])->get('active_voice_channels_keys', []);

        $channels[] = [
            'guildId' => $interaction->guild->id,
            'channelId' => $channel->id,
            'ownerId' => $interaction->user->id,
            'usersCount' => 0,
            'users' => [],
            'lastJoinedAt' => now(),
        ];

        cache()->tags(['voice_channels'])->put('active_voice_channels_keys', $channels);

        await($interaction->guild->channels->freshen());

        $this->interactionWithUser($interaction, $channel);
    }

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
            ],
        ];
    }

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
}
