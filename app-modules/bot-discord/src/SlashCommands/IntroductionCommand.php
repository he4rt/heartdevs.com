<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Interaction;
use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Actions\InformationUserAction;
use He4rt\User\DTO\UpsertInformationDTO;
use He4rt\User\Models\User;
use He4rt\User\Services\ResolveUserContextService;
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

    public function handle(Interaction $interaction): void
    {
        $hasRole = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === config('he4rt.channels.guild_rule_id'));

        if ($hasRole) {
            $interaction->respondWithMessage(
                'Você já se apresentou. Esse comando só pode ser usado uma vez.',
                true
            );

            return;
        }

        $this->modal('Apresentar')
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
            ->submit(fn (Interaction $interaction, Collection $components) => $this->persistData(
                $interaction,
                $components
            ))
            ->show($interaction);
    }

    private function persistData(Interaction $interaction, Collection $components): void
    {

        try {
            $tenantProvider = Provider::query()
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

            $userContext = resolve(ResolveUserContextService::class)->handle($userDto);

            $informationDto = UpsertInformationDTO::make([
                'user' => $userContext->user,
                'name' => $components->get('custom_id', 'name')->value,
                'nickname' => $components->get('custom_id', 'nickname')->value,
                'about' => $components->get('custom_id', 'about')->value,
                'linkedin_url' => $components->get('custom_id', 'linkedin_url')->value ?? null,
                'github_url' => $components->get('custom_id', 'github_url')->value ?? null,
                'birthdate' => null,
            ]);

            $userInformation = resolve(InformationUserAction::class)->handle($informationDto);

            $this
                ->message('Apresentação enviada com sucesso')
                ->content(
                    "Agora a comunidade já pode conhecer um pouco mais sobre você!\n"
                    .'https://heartdevs.com/'
                )
                ->color((string) hexdec('4b0080'))
                ->footerIcon($interaction->guild->icon)
                ->footerText(Date::now()->format('Y').' © He4rt Developers')
                ->timestamp(now())
                ->reply($interaction, true);

            $this
                ->message('Nova apresentação')
                ->title('Apresentação de '.$userInformation->nickname)
                ->thumbnailUrl($interaction->user->avatar)
                ->content(sprintf(
                    '<@%s> acabou de se apresentar na comunidade. Sejam bem-vindo(a) e fique à vontade para interagir.',
                    $interaction->user->id
                ))
                ->fields([
                    'Nome' => $userInformation->name,
                    'Nickname' => $userInformation->nickname,
                    'Sobre' => $userInformation->about,
                ])
                ->fields(
                    [
                        'GitHub' => $userInformation->github_url ?? '-',
                        'LinkedIn' => $userInformation->linkedin_url ?? '-',
                    ],
                    inline: false
                )
                ->footerIcon($interaction->guild->icon)
                ->footerText(Date::now()->format('Y').' © He4rt Developers')
                ->timestamp(now())
                ->color((string) hexdec('4b0080'))
                ->send(config('he4rt.channels.welcome_channel'));

            $actualRoles = [];

            foreach ($interaction->member->roles as $role) {
                $actualRoles[] = $role->id;
            }

            $actualRoles[] = config('he4rt.channels.guild_rule_id');

            $interaction->member->setRoles($actualRoles);

        } catch (Throwable $throwable) {
            $this->logger()->error('Error IntroductionCommand', [$throwable->getMessage()]);

            $interaction->respondWithMessage(
                'Ocorreu um erro ao processar sua apresentação. Por favor, tente novamente mais tarde.',
                true
            );
        }
    }
}
