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
use Illuminate\Support\Facades\Log;
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
                ephemeral: true
            );

            return;
        }

        $name = $this->memberProvider->user->name;
        $nickname = $profile->nickname;
        $about = $profile->about;

        $this->modal('Editar Perfil')
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT)
                    ->setCustomId('name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nome')
                    ->setValue(filled($name) && mb_strlen((string) $name) >= 2 ? $name : null)
                    ->setRequired(required: true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT)
                    ->setCustomId('nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Seu nickname')
                    ->setValue(filled($nickname) && mb_strlen($nickname) >= 2 ? $nickname : null)
                    ->setRequired(required: true),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH)
                    ->setCustomId('about')
                    ->setMinLength(5)
                    ->setMaxLength(500)
                    ->setPlaceholder('Fale mais sobre você...')
                    ->setValue(filled($about) && mb_strlen($about) >= 5 ? $about : null)
                    ->setRequired(required: true),

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
        /** @var object{value: string}|null $nameComponent */
        $nameComponent = $components->get('custom_id', 'name');
        /** @var object{value: string}|null $nicknameComponent */
        $nicknameComponent = $components->get('custom_id', 'nickname');
        /** @var object{value: string}|null $aboutComponent */
        $aboutComponent = $components->get('custom_id', 'about');

        $name = $nameComponent->value ?? '';
        $nickname = $nicknameComponent->value ?? '';
        $about = $aboutComponent->value ?? '';

        try {
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
                ->reply($interaction, ephemeral: true);

        } catch (Throwable $throwable) {
            Log::channel('bot-discord')->error('EditProfileCommand: failed to persist profile data', [
                'discord_user_id' => $interaction->user->id,
                'guild_id' => $interaction->guild_id,
                'user_id' => $this->memberProvider?->user?->id,
                'tenant_id' => $this->memberProvider?->tenant_id,
                'fields' => ['name' => $name, 'nickname' => $nickname, 'about' => $about],
                'exception' => $throwable,
            ]);

            report($throwable);

            $interaction->respondWithMessage('Erro ao persistir dados', ephemeral: true);
        }
    }
}
