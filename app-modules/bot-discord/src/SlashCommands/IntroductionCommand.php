<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Builders\Components\TextInput;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Interaction;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

use function React\Async\await;

class IntroductionCommand extends AbstractSlashCommand
{
    protected $name = 'apresentar';

    protected $description = 'Apresente-se no servidor!';

    /** @var array<mixed> */
    protected $options = [];

    /** @var array<mixed> */
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
            $interaction->respondWithMessage('Você já se apresentou. Esse comando só pode ser usado uma vez.', ephemeral: true);

            return;
        }

        $modalId = 'presentation_'.uniqid();

        $this->modal('Apresentar')
            ->id($modalId)
            ->components([
                TextInput::new('Nome', TextInput::STYLE_SHORT, 'name')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Fulano de Tal')
                    ->setRequired(required: true),

                TextInput::new('Nickname', TextInput::STYLE_SHORT, 'nickname')
                    ->setMinLength(2)
                    ->setMaxLength(32)
                    ->setPlaceholder('Fulano123')
                    ->setRequired(required: true),

                TextInput::new('Nos conte um pouco sobre você', TextInput::STYLE_PARAGRAPH, 'about')
                    ->setMinLength(5)
                    ->setMaxLength(500)
                    ->setPlaceholder('Entrei de curioso e acabei gostando do servidor!')
                    ->setRequired(required: true),

            ])
            ->submit(function (Interaction $interaction, Collection $components) use ($modalId): void {
                if ($interaction->data->custom_id !== $modalId) {
                    return;
                }

                try {
                    $this->persistData($interaction, $components);

                    $interaction->respondWithMessage("Apresentação enviada com sucesso.\nhttps://heartdevs.com/", ephemeral: true);

                } catch (Throwable $throwable) {
                    Log::channel('bot-discord')->error('IntroductionCommand: failed to process introduction', [
                        'discord_user_id' => $interaction->user->id,
                        'guild_id' => $interaction->guild_id,
                        'fields' => [
                            'name' => $components->get('custom_id', 'name')?->value,
                            'nickname' => $components->get('custom_id', 'nickname')?->value,
                            'about' => $components->get('custom_id', 'about')?->value,
                        ],
                        'exception' => $throwable,
                    ]);

                    report($throwable);

                    $interaction->respondWithMessage('Ocorreu um erro ao processar sua apresentação.', ephemeral: true);
                }
            })
            ->show($interaction);
    }

    /**
     * @param  Collection<mixed, mixed>  $components
     *
     * @throws Throwable
     */
    private function persistData(Interaction $interaction, Collection $components): void
    {
        $tenantId = (string) config('he4rt.tenant_id');

        $userDto = ResolveUserProviderDTO::make([
            'tenant_id' => $tenantId,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $interaction->user->id,
            'model_type' => (new User)->getMorphClass(),
            'username' => $interaction->user->username,
            'avatar' => $interaction->user->avatar,
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        /** @var object{value: string} $nameComponent */
        $nameComponent = $components->get('custom_id', 'name');
        /** @var object{value: string} $nicknameComponent */
        $nicknameComponent = $components->get('custom_id', 'nickname');
        /** @var object{value: string} $aboutComponent */
        $aboutComponent = $components->get('custom_id', 'about');

        $name = $nameComponent->value;
        $nickname = $nicknameComponent->value;
        $about = $aboutComponent->value;

        $userContext->user->update(['name' => $name]);

        $profile = Profile::ensureExists($userContext->user->id, $tenantId);

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => $nickname,
            'about' => $about,
        ]);

        resolve(UpsertProfile::class)->handle($profile, $dto);

        $this
            ->message('Nova apresentação')
            ->title('Apresentação de '.$nickname)
            ->thumbnailUrl($interaction->user->avatar)
            ->content(sprintf(
                '<@%s> acabou de se apresentar na comunidade.',
                $interaction->user->id
            ))
            ->fields([
                'Nome' => $name,
                'Nickname' => $nickname,
            ])
            ->fields(['Sobre' => $about], inline: false)
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

        await($interaction->member->setRoles($roles));
    }
}
