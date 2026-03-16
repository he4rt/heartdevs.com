<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Interaction;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Actions\InformationUserAction;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\DTOs\UpsertInformationDTO;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Date;
use Laracord\Commands\SlashCommand;
use Throwable;

class IntroductionCommand extends SlashCommand
{
    protected $name = 'apresentar';

    protected $description = 'Apresente-se no servidor!';

    protected $options = [];

    protected $permissions = [];

    protected $admin = false;

    protected $hidden = false;

    private readonly string $roleId;

    private readonly string $welcomeChannelId;

    public function __construct()
    {
        $this->roleId = config('bot-discord.roles.presentation');
        $this->welcomeChannelId = config('bot-discord.channels.presentations');
    }

    public function handle(Interaction $interaction): void
    {
        $hasRole = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === $this->roleId);

        if ($hasRole) {
            $interaction->respondWithMessage('Você já se apresentou. Esse comando só pode ser usado uma vez.', true);

            return;
        }

        $modalId = 'presentation_'.uniqid();

        $this->modal('Apresentar')
            ->id($modalId)
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT)
                    ->setCustomId('name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Fulano de Tal')
                    ->setRequired(true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT)
                    ->setCustomId('nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Fulano123')
                    ->setRequired(true),

                TextInput::new('Git/Github (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('github_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://github.com/YOUR_USERNAME')
                    ->setRequired(false),

                TextInput::new('LinkedIn (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('linkedin_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://linkedin.com/in/YOUR_USERNAME')
                    ->setRequired(false),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH)
                    ->setCustomId('about')
                    ->setMinLength(5)
                    ->setMaxLength(1000)
                    ->setPlaceholder('Entrei de curioso e acabei gostando do servidor!')
                    ->setRequired(true),

            ])
            ->submit(function (Interaction $interaction, Collection $components) use ($modalId): void {
                if ($interaction->data->custom_id !== $modalId) {
                    return;
                }

                try {
                    $this->persistData($interaction, $components);

                    $interaction->respondWithMessage("Apresentação enviada com sucesso.\nhttps://heartdevs.com/", true);

                } catch (Throwable) {
                    $interaction->respondWithMessage('Ocorreu um erro ao processar sua apresentação.', true);
                }
            })
            ->show($interaction);
    }

    private function persistData(Interaction $interaction, Collection $components): void
    {
        $tenantProvider = ExternalIdentity::query()
            ->where('model_type', Tenant::class)
            ->where('provider_id', (string) $interaction->guild_id)
            ->firstOrFail();

        $userDto = ResolveUserProviderDTO::make([
            'tenant_id' => $tenantProvider->tenant_id,
            'provider' => $tenantProvider->provider,
            'provider_id' => $interaction->user->id,
            'model_type' => User::class,
            'username' => $interaction->user->username,
            'avatar' => $interaction->user->avatar,
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        $informationDto = UpsertInformationDTO::make([
            'user' => $userContext->user,
            'name' => $components->get('custom_id', 'name')->value,
            'nickname' => $components->get('custom_id', 'nickname')->value,
            'about' => $components->get('custom_id', 'about')->value,
            'linkedin_url' => $components->get('custom_id', 'linkedin_url')?->value,
            'github_url' => $components->get('custom_id', 'github_url')?->value,
            'birthdate' => null,
        ]);

        $userInformation = resolve(InformationUserAction::class)->handle($informationDto);

        $this
            ->message('Nova apresentação')
            ->title('Apresentação de '.$userInformation->nickname)
            ->thumbnailUrl($interaction->user->avatar)
            ->content(sprintf(
                '<@%s> acabou de se apresentar na comunidade.',
                $interaction->user->id
            ))
            ->fields([
                'Nome' => $userInformation->name,
                'Nickname' => $userInformation->nickname,
            ])
            ->fields(['Sobre' => $userInformation->about], inline: false)
            ->fields([
                'GitHub' => $userInformation->github_url ?? '-',
                'LinkedIn' => $userInformation->linkedin_url ?? '-',
            ])
            ->footerIcon($interaction->guild->icon)
            ->footerText(Date::now()->format('Y').' © He4rt Developers')
            ->timestamp(now())
            ->color((string) hexdec('4b0080'))
            ->send($this->welcomeChannelId);

        $roles = [];

        foreach ($interaction->member->roles as $role) {
            $roles[] = $role->id;
        }

        $roles[] = $this->roleId;

        $interaction->member->setRoles($roles);
    }
}
