<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\Date;
use Throwable;

class EditProfileCommand extends AbstractSlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'editar-perfil';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Comando para editar seu perfil.';

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
    protected $hidden = false;

    /**
     * Handle the slash command.
     */
    public function handle(Interaction $interaction): void
    {
        $profile = Profile::query()
            ->where('user_id', $this->memberProvider?->user?->id)
            ->where('tenant_id', $this->memberProvider?->tenant_id)
            ->first();

        if (!$profile) {
            $interaction->respondWithMessage(
                'Parece que você ainda não completou sua apresentação. Use o comando `/apresentar` para continuar.',
                true
            );

            return;
        }

        $this->modal('Editar Perfil')
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT)
                    ->setCustomId('name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nome')
                    ->setValue($this->memberProvider->user->name ?? '')
                    ->setRequired(true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT)
                    ->setCustomId('nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nickname')
                    ->setValue($profile->nickname ?? '')
                    ->setRequired(true),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH)
                    ->setCustomId('about')
                    ->setMinLength(5)
                    ->setMaxLength(500)
                    ->setPlaceholder('Fale mais sobre você...')
                    ->setValue($profile->about ?? '')
                    ->setRequired(true),

            ])
            ->submit(fn (Interaction $interaction, Collection $components) => $this->persistData(
                $interaction,
                $components
            ))
            ->show($interaction);
    }

    /**
     * @param  Collection<mixed, mixed>  $components
     */
    private function persistData(
        Interaction $interaction,
        Collection $components
    ): void {
        try {
            $name = $components->get('custom_id', 'name')?->value;
            $nickname = $components->get('custom_id', 'nickname')?->value;
            $about = $components->get('custom_id', 'about')?->value;

            $this->memberProvider->user->update(['name' => $name]);

            $profile = Profile::query()
                ->where('user_id', $this->memberProvider->user->id)
                ->where('tenant_id', $this->memberProvider->tenant_id)
                ->firstOrFail();

            $dto = UpsertProfileDTO::fromArray([
                'nickname' => $nickname,
                'about' => $about,
            ]);

            resolve(UpsertProfile::class)->handle($profile, $dto);

            $this
                ->message('Perfil atualizado!')
                ->content('https://heartdevs.com/')
                ->color('800080')
                ->title('Perfil '.$nickname)
                ->thumbnailUrl($interaction->user->avatar)
                ->fields([
                    'Nome/Nickname' => $nickname,
                    'Sobre' => $about,
                ])
                ->footerIcon($interaction->guild->icon)
                ->footerText(Date::now()->format('Y').' © He4rt Developers')
                ->timestamp(now())
                ->reply($interaction, true);

        } catch (Throwable $throwable) {
            $this->logger()->error('Error EditProfileCommand:', [$throwable->getMessage()]);

            $interaction->respondWithMessage('Erro ao persistir dados', true);
        }
    }
}
