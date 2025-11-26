<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use He4rt\User\Actions\UpdateProfile;
use He4rt\User\DTO\UpdateProfileDTO;
use Throwable;

class EditProfileCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'edit-profile';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Edit Profile Command slash command.';

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
        if (! $this->memberProvider?->user?->information) {
            $interaction->respondWithMessage(
                'Parece que você ainda não completou sua apresentação. Use o comando `/introduction` para continuar.',
                true);

            return;
        }

        $profile = $this->memberProvider->user->information;

        $this->modal('Editar Perfil')
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT)
                    ->setCustomId('name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nome')
                    ->setValue($profile->name ?? '')
                    ->setRequired(true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT)
                    ->setCustomId('nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nickname')
                    ->setValue($profile->nickname ?? '')
                    ->setRequired(true),

                TextInput::new('Git/Github (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('github_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://github.com/...')
                    ->setValue($profile->github_url ?? '')
                    ->setRequired(false),

                TextInput::new('Linkedisney (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('linkedin_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://linkedin.com/in/...')
                    ->setValue($profile->linkedin_url ?? '')
                    ->setRequired(false),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH)
                    ->setCustomId('about')
                    ->setMinLength(5)
                    ->setMaxLength(1000)
                    ->setPlaceholder('Fale mais sobre você...')
                    ->setValue($profile->about ?? '')
                    ->setRequired(true),

            ])
            ->submit(fn (Interaction $interaction, Collection $components) => $this->persistData($interaction,
                $components))
            ->show($interaction);
    }

    private function persistData(
        Interaction $interaction,
        Collection $components
    ): void {
        try {
            $payload = UpdateProfileDTO::fromPayload([
                'tenant_id' => $this->memberProvider->tenant_id,
                'provider' => $this->memberProvider->provider,
                'provider_id' => $interaction->user->id,
                'name' => $components->get('custom_id', 'name')->value,
                'nickname' => $components->get('custom_id', 'nickname')->value,
                'linkedin_url' => $components->get('custom_id', 'linkedin_url')->value,
                'github_url' => $components->get('custom_id', 'github_url')->value,
                'birthdate' => $components->get('custom_id', 'birthdate')?->value ?? null,
                'about' => $components->get('custom_id', 'about')->value,
            ]);

            app(UpdateProfile::class)->handle($payload);

            $this
                ->message('Perfil atualizado!')
                ->content('https://heartdevs.com/')
                ->color('800080')
                ->title('Perfil '.$payload->nickname)
                ->thumbnailUrl($interaction->user->avatar)
                ->fields([ // max 3 fields per row
                    'Nome/Nickname' => $payload->nickname,
                    'Sobre' => $payload->about,
                ])
                ->fields(
                    [ // max 3 fields per row
                        'Git/Github' => $payload->githubUrl ?? '-',
                        'Linkedin' => $payload->linkedinUrl ?? '-',
                    ],
                    inline: false
                )
                ->footerIcon($interaction->guild->icon)
                ->footerText('HE4RT INC')
                ->timestamp(now())
                ->reply($interaction, true);

        } catch (Throwable $throwable) {
            $this->logger()->error($throwable->getMessage());

            $interaction->respondWithMessage('Erro ao persistir dados', true);
        }
    }
}
