<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

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
use Illuminate\Support\Facades\Cache;
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

    private readonly array $technologyRoles;

    private array $technologyOptions = [
        'javascript' => [
            'label' => 'JavaScript',
            'description' => 'Linguagem de programação versátil',
            'emoji' => '🟨',
        ],
        'php' => [
            'label' => 'PHP',
            'description' => 'Linguagem para desenvolvimento web',
            'emoji' => '🐘',
        ],
        'python' => [
            'label' => 'Python',
            'description' => 'Linguagem de alto nível',
            'emoji' => '🐍',
        ],
        'java' => [
            'label' => 'Java',
            'description' => 'Linguagem orientada a objetos',
            'emoji' => '☕',
        ],
        'c_c++_c#' => [
            'label' => 'C/C++/C#',
            'description' => 'Linguagens de sistema e .NET',
            'emoji' => '⚡',
        ],
        'rust' => [
            'label' => 'Rust',
            'description' => 'Linguagem de sistema segura',
            'emoji' => '🦀',
        ],
        'ruby' => [
            'label' => 'Ruby',
            'description' => 'Linguagem elegante e produtiva',
            'emoji' => '💎',
        ],
        'elixir' => [
            'label' => 'Elixir',
            'description' => 'Linguagem funcional e concorrente',
            'emoji' => '💜',
        ],
        'perl' => [
            'label' => 'Perl',
            'description' => 'Linguagem de processamento de texto',
            'emoji' => '🐪',
        ],
        'gamedev' => [
            'label' => 'Game Development',
            'description' => 'Desenvolvimento de jogos',
            'emoji' => '🎮',
        ],
        'designer' => [
            'label' => 'Design',
            'description' => 'Design gráfico e visual',
            'emoji' => '🎨',
        ],
        'ux_ui' => [
            'label' => 'UX/UI',
            'description' => 'Experiência e interface do usuário',
            'emoji' => '📱',
        ],
        'basic_english' => [
            'label' => 'Inglês Básico',
            'description' => 'Nível iniciante de inglês',
            'emoji' => '🇺🇸',
        ],
        'intermediate_english' => [
            'label' => 'Inglês Intermediário',
            'description' => 'Nível intermediário de inglês',
            'emoji' => '🇬🇧',
        ],
        'advanced_english' => [
            'label' => 'Inglês Avançado',
            'description' => 'Nível avançado de inglês',
            'emoji' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
        ],
        'he4rt_delas' => [
            'label' => 'He4rt Delas ♀️',
            'description' => 'Comunidade para mulheres, pessoas não binárias e que se identificam como mulheres',
            'emoji' => '💖',
        ],
    ];

    public function __construct()
    {
        $this->roleId = config('bot-discord.roles.presentation');
        $this->welcomeChannelId = config('bot-discord.channels.presentations');
        $this->technologyRoles = config('bot-discord.roles.technologies');
    }

    public function interactions(): array
    {
        return [
            'tech_selection' => fn (Interaction $interaction) => $this->finalizePresentation($interaction,
                $interaction->data->values ?? []),

            'skip_tech' => fn (Interaction $interaction) => $this->finalizePresentation($interaction, []),
        ];
    }

    public function handle(Interaction $interaction): void
    {
        $hasRole = $interaction->member->roles
            ->find(fn (Role $role) => $role->id === $this->roleId);

        if ($hasRole) {
            $interaction->respondWithMessage('Você já se apresentou. Esse comando só pode ser usado uma vez.', true);

            return;
        }

        $this->showPersonalInfoModal($interaction);
    }

    private function showPersonalInfoModal(Interaction $interaction): void
    {
        $modalId = 'presentation_personal_'.uniqid();

        $this->modal('Apresentar - Informações Pessoais')
            ->id($modalId)
            ->text(label: 'Nome', key: 'name', minLength: 2, maxLength: 32, placeholder: 'Fulano de Tal',
                required: true)
            ->text(label: 'Nickname', key: 'nickname', minLength: 2, maxLength: 32, placeholder: 'Fulano123',
                required: true)
            ->text(label: 'Git/Github (Opcional)', key: 'github_url', maxLength: 60,
                placeholder: 'https://github.com/YOUR_USERNAME',
                required: false)
            ->text(label: 'LinkedIn (Opcional)', key: 'linkedin_url', maxLength: 60,
                placeholder: 'https://linkedin.com/in/YOUR_USERNAME',
                required: false)
            ->paragraph(label: 'Nos conte um pouco sobre você', key: 'about', minLength: 5,
                maxLength: 1000, placeholder: 'Entrei de curioso e acabei gostando do servidor!', required: true)
            ->submit(function (Interaction $interaction, Collection $components) use ($modalId): void {
                if ($interaction->data->custom_id !== $modalId) {
                    return;
                }

                try {
                    $this->savePersonalInfoAndShowTechSelection($interaction, $components);
                } catch (Throwable) {
                    $interaction->respondWithMessage('Ocorreu um erro ao processar sua apresentação.', true);
                }
            })
            ->show($interaction);
    }

    private function savePersonalInfoAndShowTechSelection(Interaction $interaction, Collection $components): void
    {
        $sessionKey = 'presentation_data_'.$interaction->user->id;
        $personalData = [
            'name' => $components->get('custom_id', 'name')->value,
            'nickname' => $components->get('custom_id', 'nickname')->value,
            'github_url' => $components->get('custom_id', 'github_url')?->value,
            'linkedin_url' => $components->get('custom_id', 'linkedin_url')?->value,
            'about' => $components->get('custom_id', 'about')->value,
            'user_id' => $interaction->user->id,
            'guild_id' => $interaction->guild_id,
            'timestamp' => now()->toISOString(),
        ];

        Cache::put($sessionKey, $personalData, 600);

        $this->showTechnologySelection($interaction);
    }

    private function showTechnologySelection(Interaction $interaction): void
    {
        $selectOptions = [];
        foreach ($this->technologyOptions as $key => $tech) {
            $selectOptions[$key] = [
                'label' => $tech['label'],
                'description' => $tech['description'],
                'emoji' => $tech['emoji'],
            ];
        }

        $this->message('Selecione suas tecnologias')
            ->title('Etapa 2: Suas Tecnologias')
            ->content('Selecione as tecnologias que você conhece ou tem interesse. Esta etapa é **opcional** - você pode pular clicando em "Finalizar sem tecnologias".')
            ->select($selectOptions, placeholder: 'Selecione suas tecnologias (opcional)', minValues: 0, maxValues: 16,
                route: 'tech_selection')
            ->button(label: 'Finalizar sem tecnologias', style: 'secondary', route: 'skip_tech')
            ->reply($interaction, ephemeral: true);
    }

    private function finalizePresentation(Interaction $interaction, array $selectedTechnologies): void
    {
        $sessionKey = 'presentation_data_'.$interaction->user->id;
        $personalData = Cache::get($sessionKey);

        if (! $personalData) {
            $interaction->respondWithMessage('Sessão expirada. Por favor, execute o comando novamente.', true);

            return;
        }

        try {
            $this->persistData($interaction, $personalData, $selectedTechnologies);

            Cache::forget($sessionKey);

            $techCount = count($selectedTechnologies);
            $techList = $techCount > 0
                ? implode(', ', array_map(fn ($tech
                ) => $this->technologyOptions[$tech]['emoji'].' '.$this->technologyOptions[$tech]['label'],
                    $selectedTechnologies))
                : 'Nenhuma selecionada';

            $interaction->respondWithMessage(
                "**Apresentação enviada com sucesso!**\n\n"
                ."**Tecnologias selecionadas**: {$techList}\n\n"
                ."Bem-vindo(a) à comunidade He4rt Developers!\n"
                .'https://heartdevs.com/',
                true
            );

        } catch (Throwable) {
            Cache::forget($sessionKey);
            $interaction->respondWithMessage('Ocorreu um erro ao processar sua apresentação. Tente novamente.', true);
        }
    }

    private function persistData(Interaction $interaction, array $personalData, array $selectedTechnologies): void
    {
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
            'name' => $personalData['name'],
            'nickname' => $personalData['nickname'],
            'about' => $personalData['about'],
            'linkedin_url' => $personalData['linkedin_url'],
            'github_url' => $personalData['github_url'],
            'birthdate' => null,
        ]);

        $userInformation = resolve(InformationUserAction::class)->handle($informationDto);

        $technologiesField = '';

        if ($selectedTechnologies !== []) {
            $techLabels = array_map(function ($tech): string {
                $techInfo = $this->technologyOptions[$tech] ?? ['emoji' => '', 'label' => $tech];

                return $techInfo['emoji'].' '.$techInfo['label'];
            }, $selectedTechnologies);
            $technologiesField = implode(', ', $techLabels);
        } else {
            $technologiesField = 'Nenhuma selecionada';
        }

        $embedFields = [
            'Nome' => $userInformation->name,
            'Nickname' => $userInformation->nickname,
        ];

        $this
            ->message('Nova apresentação')
            ->title('Apresentação de '.$userInformation->nickname)
            ->thumbnailUrl($interaction->user->avatar)
            ->content(sprintf(
                '<@%s> acabou de se apresentar na comunidade.',
                $interaction->user->id
            ))
            ->fields($embedFields)
            ->fields(['Sobre' => $userInformation->about], inline: false)
            ->fields([
                'GitHub' => $userInformation->github_url ?? '-',
                'LinkedIn' => $userInformation->linkedin_url ?? '-',
            ])
            ->fields(['Tecnologias' => $technologiesField], inline: false)
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

        foreach ($selectedTechnologies as $tech) {
            if (isset($this->technologyRoles[$tech])) {
                $roles[] = $this->technologyRoles[$tech];
            }
        }

        $roles = array_unique($roles);

        $interaction->member->setRoles($roles);
    }
}
