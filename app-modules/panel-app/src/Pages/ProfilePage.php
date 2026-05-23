<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\ToggleAvailability;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * @property-read Schema $form
 */
class ProfilePage extends Page
{
    use WithFileUploads;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var TemporaryUploadedFile|null */
    public $avatarUpload;

    /** @var TemporaryUploadedFile|null */
    public $coverUpload;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $title = 'Profile';

    protected static ?string $slug = 'profile';

    protected static ?int $navigationSort = 2;

    protected string $view = 'panel-app::pages.profile';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        $profile = $this->getRecord();

        $this->form->fill([
            'nickname' => $profile->nickname,
            'birthdate' => $profile->birthdate?->format('Y-m-d'),
            'headline' => $profile->headline,
            'seniority_level' => $profile->seniority_level,
            'years_experience' => $profile->years_experience,
            'about' => $profile->about,
            'social_links' => $this->socialLinksToRepeater($profile->social_links),
            'available_for_proposals' => $profile->available_for_proposals,
            'start_availability' => $profile->start_availability,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('panel-app::profile.sections.professional'))
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('headline')
                                    ->label(__('panel-app::profile.fields.headline'))
                                    ->placeholder(__('panel-app::profile.placeholders.headline'))
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Select::make('seniority_level')
                                    ->label(__('panel-app::profile.fields.seniority_level'))
                                    ->options(SeniorityLevel::class)
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('years_experience')
                                    ->label(__('panel-app::profile.fields.years_experience'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(50)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),
                                Textarea::make('about')
                                    ->label(__('panel-app::profile.fields.about'))
                                    ->placeholder(__('panel-app::profile.placeholders.about'))
                                    ->maxLength(500)
                                    ->rows(4)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.address'))
                        ->relationship('address')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('country')
                                    ->label(__('panel-app::profile.fields.country'))
                                    ->options([
                                        'BRA' => '🇧🇷 Brasil',
                                        'USA' => '🇺🇸 United States',
                                        'PRT' => '🇵🇹 Portugal',
                                        'ARG' => '🇦🇷 Argentina',
                                        'DEU' => '🇩🇪 Deutschland',
                                        'CAN' => '🇨🇦 Canada',
                                        'GBR' => '🇬🇧 United Kingdom',
                                        'FRA' => '🇫🇷 France',
                                        'ESP' => '🇪🇸 España',
                                        'ITA' => '🇮🇹 Italia',
                                        'JPN' => '🇯🇵 Japan',
                                        'AUS' => '🇦🇺 Australia',
                                        'MEX' => '🇲🇽 México',
                                        'COL' => '🇨🇴 Colombia',
                                        'CHL' => '🇨🇱 Chile',
                                        'URY' => '🇺🇾 Uruguay',
                                        'IRL' => '🇮🇪 Ireland',
                                        'NLD' => '🇳🇱 Nederland',
                                    ])
                                    ->default('BRA')
                                    ->searchable()
                                    ->live()
                                    ->columnSpan(1),

                                Select::make('state')
                                    ->label(__('panel-app::profile.fields.state'))
                                    ->options(fn (Get $get): array => ($get('country') ?? 'BRA') === 'BRA' ? [
                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
                                        'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
                                        'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
                                        'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
                                        'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe',
                                        'TO' => 'Tocantins',
                                    ] : [])
                                    ->searchable()
                                    ->allowHtml(false)
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('city')
                                    ->label(__('panel-app::profile.fields.city'))
                                    ->placeholder('São Paulo')
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),
                            ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.social_links'))
                        ->schema([
                            Repeater::make('social_links')
                                ->label('')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('platform')
                                            ->label(__('panel-app::profile.fields.platform'))
                                            ->options(SocialPlatform::class)
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('handle')
                                            ->label(__('panel-app::profile.fields.handle'))
                                            ->placeholder(__('panel-app::profile.placeholders.handle'))
                                            ->required()
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->addActionLabel(__('panel-app::profile.actions.add_social_link'))
                                ->defaultItems(0)
                                ->reorderable(false)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('panel-app::profile.sections.availability'))
                        ->schema([
                            Toggle::make('available_for_proposals')
                                ->label(__('panel-app::profile.fields.available_for_proposals'))
                                ->hint(__('panel-app::profile.hints.available_for_proposals'))
                                ->live(),

                            Select::make('start_availability')
                                ->label(__('panel-app::profile.fields.start_availability'))
                                ->options(StartAvailability::class)
                                ->live()
                                ->visible(fn (Get $get): bool => (bool) $get('available_for_proposals')),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('panel-app::profile.actions.save'))
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->record(auth()->user())
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();
        $profile = $this->getRecord();

        $socialLinks = $this->repeaterToSocialLinks($formData['social_links'] ?? []);

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => $this->data['nickname'] ?? null,
            'birthdate' => $this->data['birthdate'] ?? null,
            'about' => $formData['about'] ?? null,
            'headline' => $formData['headline'] ?? null,
            'seniority_level' => $formData['seniority_level'] ?? null,
            'years_experience' => $formData['years_experience'] ?? null,
            'social_links' => $socialLinks !== [] ? $socialLinks : null,
        ]);

        resolve(UpsertProfile::class)->handle($profile, $dto);

        $available = (bool) ($formData['available_for_proposals'] ?? false);
        $rawStartAvailability = $formData['start_availability'] ?? null;
        $startAvailability = match (true) {
            $rawStartAvailability instanceof StartAvailability => $rawStartAvailability,
            is_string($rawStartAvailability) => StartAvailability::from($rawStartAvailability),
            $available => StartAvailability::Negotiable,
            default => null,
        };

        resolve(ToggleAvailability::class)->handle($profile, $available, $startAvailability);

        $this->saveMedia();
        $this->form->saveRelationships();

        Notification::make()
            ->success()
            ->title(__('panel-app::profile.notifications.saved'))
            ->send();
    }

    public function getRecord(): Profile
    {
        return Profile::query()
            ->firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'tenant_id' => filament()->getTenant()?->getKey(),
                ],
            );
    }

    #[Computed]
    public function character(): ?Character
    {
        return Character::query()
            ->with('badges')
            ->where('user_id', auth()->id())
            ->where('tenant_id', filament()->getTenant()?->getKey())
            ->first();
    }

    #[Computed]
    public function initials(): string
    {
        return Str::of(auth()->user()->name)
            ->explode(' ')
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    #[Computed]
    public function avatarPreviewUrl(): ?string
    {
        if ($this->avatarUpload instanceof TemporaryUploadedFile) {
            return $this->avatarUpload->temporaryUrl();
        }

        return auth()->user()->getFirstMediaUrl('avatar') ?: null;
    }

    #[Computed]
    public function coverPreviewUrl(): ?string
    {
        if ($this->coverUpload instanceof TemporaryUploadedFile) {
            return $this->coverUpload->temporaryUrl();
        }

        return auth()->user()->getFirstMediaUrl('cover') ?: null;
    }

    public function removeAvatar(): void
    {
        $this->avatarUpload = null;
        auth()->user()->clearMediaCollection('avatar');
    }

    public function removeCover(): void
    {
        $this->coverUpload = null;
        auth()->user()->clearMediaCollection('cover');
    }

    private function saveMedia(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if ($this->avatarUpload instanceof TemporaryUploadedFile) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($this->avatarUpload->getRealPath())
                ->usingFileName($this->avatarUpload->getClientOriginalName())
                ->toMediaCollection('avatar');
            $this->avatarUpload = null;
        }

        if ($this->coverUpload instanceof TemporaryUploadedFile) {
            $user->clearMediaCollection('cover');
            $user->addMedia($this->coverUpload->getRealPath())
                ->usingFileName($this->coverUpload->getClientOriginalName())
                ->toMediaCollection('cover');
            $this->coverUpload = null;
        }
    }

    /**
     * @param  array<string, string>|null  $socialLinks
     * @return list<array{platform: string, handle: string}>
     */
    private function socialLinksToRepeater(?array $socialLinks): array
    {
        if ($socialLinks === null) {
            return [];
        }

        $result = [];

        foreach ($socialLinks as $platform => $handle) {
            $result[] = ['platform' => $platform, 'handle' => $handle];
        }

        return $result;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $repeaterData
     * @return array<string, string>
     */
    private function repeaterToSocialLinks(array $repeaterData): array
    {
        $links = [];

        foreach ($repeaterData as $item) {
            $platform = $item['platform'] ?? null;
            $handle = $item['handle'] ?? null;

            if (filled($platform) && filled($handle)) {
                $key = $platform instanceof SocialPlatform ? $platform->value : (string) $platform;
                $links[$key] = (string) $handle;
            }
        }

        return $links;
    }
}
