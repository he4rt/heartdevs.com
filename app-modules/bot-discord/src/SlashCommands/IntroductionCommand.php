<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Interaction;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Actions\UpdateProfile;
use He4rt\User\DTO\UpdateProfileDTO;
use Laracord\Commands\SlashCommand;
use React\Promise\PromiseInterface;
use Throwable;

class IntroductionCommand extends SlashCommand
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'introduction';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'The Introduction Command slash command.';

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

        $this->modal('Apresentar')
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT)
                    ->setCustomId('name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Pride')
                    ->setValue('Pride')
                    ->setRequired(true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT)
                    ->setCustomId('nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Pride')
                    ->setValue('Pride')
                    ->setRequired(true),

                TextInput::new('Git/Github (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('github_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://github.com/YOUR_USERNAME')
                    ->setValue('https://github.com/YOUR_USERNAME')
                    ->setRequired(false),

                TextInput::new('Linkedisney (Opcional)', TextInput::STYLE_SHORT)
                    ->setCustomId('linkedin_url')
                    ->setMinLength(0)
                    ->setMaxLength(60)
                    ->setPlaceholder('https://linkedin.com/in/YOUR_USERNAME')
                    ->setValue('https://linkedin.com/in/YOUR_USERNAME')
                    ->setRequired(false),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH)
                    ->setCustomId('about')
                    ->setMinLength(5)
                    ->setMaxLength(1000)
                    ->setPlaceholder('Entrei de curioso e acabei gostando do servidor!')
                    ->setValue(fake()->paragraph(10))
                    ->setRequired(true),

            ])
            ->submit(fn (Interaction $interaction, Collection $components) => $this->persistData($interaction, $components))
            ->show($interaction);
    }

    private function persistData(Interaction $interaction, Collection $components): PromiseInterface
    {
        $role = $interaction->guild->roles->find(fn (Role $role) => $role->name === 'He4rt');

        if (! $role) {
            return $interaction->respondWithMessage('Erro ao encontrar o role He4rt');
        }

        $hasRole = $interaction->member->roles->find(fn (Role $item) => $item->id === $role->id);

        if ($hasRole) {
            return $interaction->respondWithMessage('Você já apresentou!!');
        }

        try {
            $tenantProvider = Provider::query()
                ->where('model_type', Tenant::class)
                ->where('provider_id', (string) $interaction->guild_id)
                ->firstOrFail();

            $payload = UpdateProfileDTO::fromPayload([
                'tenant_id' => $tenantProvider->tenant_id,
                'provider' => $tenantProvider->provider,
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
                ->message('apresentou')
                ->content('https://heartdevs.com/')
                ->color('800080')
                ->title('Apresentação '.$payload->nickname)
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
                ->send($interaction->channel->id);

            $interaction->member->addRole($role);

            return $interaction->respondWithMessage('Dados salvos com sucesso!', true);
        } catch (Throwable $throwable) {
            $this->logger()->error($throwable->getMessage());

            return $interaction->respondWithMessage('Erro ao persistir dados');
        }
    }
}
