<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Pages;

use App\Geo\Support\GeoLocation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\SyncProfileSkills;
use He4rt\Profile\Actions\ToggleAvailability;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\ProfileSkillDTO;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Enums\EmploymentType;
use He4rt\Profile\Enums\SeniorityLevel;
use He4rt\Profile\Enums\SkillProficiency;
use He4rt\Profile\Enums\SocialPlatform;
use He4rt\Profile\Enums\StartAvailability;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
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
            'skills' => $this->skillsToRepeater($profile),
            'available_for_proposals' => $profile->available_for_proposals,
            'start_availability' => $profile->start_availability,
            'expected_salary_min' => $profile->expected_salary_min,
            'expected_salary_max' => $profile->expected_salary_max,
            'has_disability' => $profile->preferences->hasDisability,
            'willing_to_relocate' => $profile->preferences->willingToRelocate,
            'is_open_to_remote' => $profile->preferences->isOpenToRemote,
            'employment_types' => array_map(
                static fn (EmploymentType $type): string => $type->value,
                $profile->preferences->employmentTypes,
            ),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('panel-app::profile.sections.personal'))
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('nickname')
                                    ->label(__('panel-app::profile.fields.nickname'))
                                    ->placeholder(__('panel-app::profile.placeholders.nickname'))
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                DatePicker::make('birthdate')
                                    ->label(__('panel-app::profile.fields.birthdate'))
                                    ->native(condition: false)
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d')
                                    ->minDate(now()->subYears(120))
                                    ->maxDate(now())
                                    ->suffixIcon(Heroicon::Calendar, isInline: true)
                                    ->extraAttributes([
                                        // Suffix icon sits outside the trigger button; open the panel on icon click.
                                        'x-on:click' => <<<'JS'
                                            if (! $event.target.closest('.fi-input-wrp-suffix')) return;
                                            if ($event.target.closest('.fi-input-wrp-actions')) return;
                                            $el.querySelector('button.fi-fo-date-time-picker-trigger')?.click();
                                        JS,
                                    ])
                                    ->columnSpan(1),
                            ]),
                        ]),

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

                    Section::make(__('panel-app::profile.sections.skills'))
                        ->description(__('panel-app::profile.hints.skills'))
                        ->schema([
                            Repeater::make('skills')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        Select::make('skill_id')
                                            ->label(__('panel-app::profile.fields.skill'))
                                            ->searchable()
                                            ->getSearchResultsUsing(fn (string $search, Select $component): array => Skill::search(
                                                $search,
                                                exclude: $this->skillIdsInSiblingRows($component),
                                            ))
                                            ->getOptionLabelUsing(fn (?string $value): ?string => $value === null ? null : (Skill::labelsById()[$value] ?? null))
                                            ->optionsLimit(50)
                                            ->distinct()
                                            ->required()
                                            ->columnSpan(1),

                                        Select::make('proficiency')
                                            ->label(__('panel-app::profile.fields.proficiency'))
                                            ->options(SkillProficiency::class)
                                            ->required()
                                            ->columnSpan(1),

                                        TextInput::make('years_experience')
                                            ->label(__('panel-app::profile.fields.skill_years_experience'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50)
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->addActionLabel(__('panel-app::profile.actions.add_skill'))
                                ->defaultItems(0)
                                ->reorderable(condition: false)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('panel-app::profile.sections.address'))
                        ->relationship('address')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('country')
                                    ->label(__('panel-app::profile.fields.country'))
                                    ->options(static fn (): array => GeoLocation::countries())
                                    ->default('BRA')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(static function (Set $set): void {
                                        $set('state', null);
                                        $set('city', null);
                                    })
                                    ->columnSpan(1),

                                Select::make('state')
                                    ->label(__('panel-app::profile.fields.state'))
                                    ->options(static fn (Get $get): array => GeoLocation::statesFor($get('country')))
                                    ->searchable()
                                    ->live()
                                    ->visible(static fn (Get $get): bool => filled($get('country')))
                                    ->afterStateUpdated(static function (Set $set): void {
                                        $set('city', null);
                                    })
                                    ->columnSpan(1),

                                Select::make('city')
                                    ->label(__('panel-app::profile.fields.city'))
                                    ->options(static fn (Get $get): array => GeoLocation::citiesFor($get('country'), $get('state')))
                                    ->getSearchResultsUsing(static fn (string $search, Get $get): array => GeoLocation::citiesFor($get('country'), $get('state'), $search))
                                    ->getOptionLabelUsing(static fn (?string $value): ?string => $value)
                                    ->searchable()
                                    ->preload()
                                    ->searchPrompt(__('panel-app::profile.placeholders.city_search'))
                                    ->hintIcon('heroicon-o-information-circle', __('panel-app::profile.hints.city'))
                                    ->visible(static fn (Get $get): bool => filled($get('state')))
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
                                ->reorderable(condition: false)
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

                            Grid::make(2)
                                ->visible(fn (Get $get): bool => (bool) $get('available_for_proposals'))
                                ->schema([
                                    TextInput::make('expected_salary_min')
                                        ->label(__('panel-app::profile.fields.expected_salary_min'))
                                        ->hint(__('panel-app::profile.hints.expected_salary'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('R$')
                                        ->live(onBlur: true),

                                    TextInput::make('expected_salary_max')
                                        ->label(__('panel-app::profile.fields.expected_salary_max'))
                                        ->numeric()
                                        ->minValue(0)
                                        ->prefix('R$')
                                        ->live(onBlur: true),
                                ]),
                        ]),

                    Section::make(__('panel-app::profile.sections.preferences'))
                        ->schema([
                            Toggle::make('is_open_to_remote')
                                ->label(__('panel-app::profile.fields.is_open_to_remote'))
                                ->live(),

                            Toggle::make('willing_to_relocate')
                                ->label(__('panel-app::profile.fields.willing_to_relocate'))
                                ->live(),

                            Toggle::make('has_disability')
                                ->label(__('panel-app::profile.fields.has_disability'))
                                ->hint(__('panel-app::profile.hints.has_disability'))
                                ->live(),

                            Select::make('employment_types')
                                ->label(__('panel-app::profile.fields.employment_types'))
                                ->options(EmploymentType::class)
                                ->multiple()
                                ->live(),
                        ]),

                    Section::make(__('panel-app::profile.sections.work_experiences'))
                        ->schema([
                            Repeater::make('workExperiences')
                                ->relationship('workExperiences')
                                ->model(fn (): Profile => $this->getRecord()) // record do schema e o User; a relacao vive na Profile
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('company_name')
                                            ->label(__('panel-app::profile.fields.company_name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        TextInput::make('position')
                                            ->label(__('panel-app::profile.fields.position'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                    ]),
                                    Textarea::make('description')
                                        ->label(__('panel-app::profile.fields.experience_description'))
                                        ->required()
                                        ->rows(3)
                                        ->maxLength(2_000)
                                        ->columnSpanFull(),
                                    Grid::make(2)->schema([
                                        DatePicker::make('start_date')
                                            ->label(__('panel-app::profile.fields.start_date'))
                                            ->native(condition: false)
                                            ->displayFormat('M Y')
                                            ->format('Y-m-d')
                                            ->maxDate(now())
                                            ->required()
                                            ->columnSpan(1),
                                        DatePicker::make('end_date')
                                            ->label(__('panel-app::profile.fields.end_date'))
                                            ->native(condition: false)
                                            ->displayFormat('M Y')
                                            ->format('Y-m-d')
                                            ->maxDate(now())
                                            ->afterOrEqual('start_date')
                                            ->required(fn (Get $get): bool => !$get('is_currently_working_here'))
                                            ->hidden(fn (Get $get): bool => (bool) $get('is_currently_working_here'))
                                            ->columnSpan(1),
                                    ]),
                                    Toggle::make('is_currently_working_here')
                                        ->label(__('panel-app::profile.fields.is_currently_working_here'))
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, bool $state) => $state ? $set('end_date', null) : null),
                                ])
                                ->itemLabel(static function (array $state): ?string {
                                    $companyName = $state['company_name'] ?? null;

                                    return is_string($companyName) ? $companyName : null;
                                })
                                ->addActionLabel(__('panel-app::profile.actions.add_work_experience'))
                                ->collapsible()
                                ->defaultItems(0)
                                ->columnSpanFull()
                                ->mutateRelationshipDataBeforeCreateUsing($this->normalizeWorkExperienceData(...))
                                ->mutateRelationshipDataBeforeSaveUsing($this->normalizeWorkExperienceData(...)),
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
            'expected_salary_min' => $formData['expected_salary_min'] ?? null,
            'expected_salary_max' => $formData['expected_salary_max'] ?? null,
            'preferences' => [
                'has_disability' => $formData['has_disability'] ?? false,
                'willing_to_relocate' => $formData['willing_to_relocate'] ?? false,
                'is_open_to_remote' => $formData['is_open_to_remote'] ?? false,
                'employment_types' => $formData['employment_types'] ?? [],
            ],
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

        resolve(SyncProfileSkills::class)->handle($profile, $this->repeaterToSkills($formData['skills'] ?? []));

        $this->form->saveRelationships();

        Notification::make()
            ->success()
            ->title(__('panel-app::profile.notifications.saved'))
            ->send();
    }

    public function editAvatarAction(): Action
    {
        return Action::make('editAvatar')
            ->label(__('panel-app::profile.actions.change_avatar'))
            ->modalHeading(__('panel-app::profile.actions.change_avatar'))
            ->modalSubmitActionLabel(__('panel-app::profile.actions.save_avatar'))
            ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
            ->schema([
                FileUpload::make('avatar')
                    ->label(__('panel-app::profile.fields.avatar'))
                    ->avatar()
                    ->imageEditor()
                    ->circleCropper()
                    ->imageEditorAspectRatioOptions(['1:1'])
                    ->storeFiles(condition: false)
                    ->required()
                    ->maxSize(2_048),
            ])
            ->action(function (array $data): void {
                $avatar = $data['avatar'] ?? null;

                if (is_string($avatar)) {
                    $avatar = TemporaryUploadedFile::createFromLivewire($avatar);
                }

                if (!$avatar instanceof TemporaryUploadedFile) {
                    return;
                }

                $this->replaceMedia('avatar', $avatar);

                Notification::make()
                    ->success()
                    ->title(__('panel-app::profile.notifications.avatar_updated'))
                    ->send();
            });
    }

    public function editCoverAction(): Action
    {
        return Action::make('editCover')
            ->label(__('panel-app::profile.actions.change_cover'))
            ->modalHeading(__('panel-app::profile.actions.change_cover'))
            ->modalSubmitActionLabel(__('panel-app::profile.actions.save_cover'))
            ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
            ->schema([
                FileUpload::make('cover')
                    ->label(__('panel-app::profile.fields.cover'))
                    ->image()
                    ->panelAspectRatio('3:1')
                    ->imageEditor()
                    ->imageAspectRatio('3:1')
                    ->imageEditorAspectRatioOptions(['3:1'])
                    ->automaticallyCropImagesToAspectRatio()
                    ->automaticallyResizeImagesMode('cover')
                    ->automaticallyResizeImagesToWidth('1800')
                    ->automaticallyResizeImagesToHeight('600')
                    ->storeFiles(condition: false)
                    ->required()
                    ->maxSize(4_096),
            ])
            ->action(function (array $data): void {
                $cover = $data['cover'] ?? null;

                if (is_string($cover)) {
                    $cover = TemporaryUploadedFile::createFromLivewire($cover);
                }

                if (!$cover instanceof TemporaryUploadedFile) {
                    return;
                }

                $this->replaceMedia('cover', $cover);

                Notification::make()
                    ->success()
                    ->title(__('panel-app::profile.notifications.cover_updated'))
                    ->send();
            });
    }

    public function getRecord(): Profile
    {
        return Profile::query()
            ->firstOrCreate(
                [
                    'user_id' => auth()->id(),
                ],
            );
    }

    #[Computed]
    public function character(): ?Character
    {
        return Character::query()
            ->with('badges')
            ->where('user_id', auth()->id())
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
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->getFirstMediaUrl('avatar') ?: null;
    }

    #[Computed]
    public function coverPreviewUrl(): ?string
    {
        /** @var User $user */
        $user = auth()->user()->fresh();

        return $user->getFirstMediaUrl('cover') ?: null;
    }

    public function removeAvatar(): void
    {
        auth()->user()->clearMediaCollection('avatar');
    }

    public function removeCover(): void
    {
        auth()->user()->clearMediaCollection('cover');
    }

    private function replaceMedia(string $collection, TemporaryUploadedFile $file): void
    {
        /** @var User $user */
        $user = auth()->user();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $user->clearMediaCollection($collection);
        $user->addMedia($file->getRealPath())
            ->usingFileName(Str::uuid()->toString().'.'.$extension)
            ->toMediaCollection($collection);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeWorkExperienceData(array $data): array
    {
        if ($data['is_currently_working_here'] ?? false) {
            $data['end_date'] = null;
        }

        return $data;
    }

    /**
     * @return list<array{skill_id: string, proficiency: SkillProficiency, years_experience: int|null}>
     */
    private function skillsToRepeater(Profile $profile): array
    {
        $skills = [];

        foreach ($profile->profileSkills()->get() as $profileSkill) {
            $skills[] = [
                'skill_id' => $profileSkill->skill_id,
                'proficiency' => $profileSkill->proficiency,
                'years_experience' => $profileSkill->years_experience,
            ];
        }

        return $skills;
    }

    /**
     * Skill ids already chosen in the other rows of the skills repeater, so the
     * search can omit them and each skill is only pickable once.
     *
     * @return list<string>
     */
    private function skillIdsInSiblingRows(Select $component): array
    {
        $repeater = $component->getParentRepeater();
        if ($repeater === null) {
            return [];
        }

        /** @var array<int|string, array<string, mixed>> $rows */
        $rows = $repeater->getRawState();

        return array_values(
            collect($rows)
                ->pluck('skill_id')
                ->reject(fn (mixed $id): bool => blank($id) || $id === $component->getState())
                ->map(fn (mixed $id): string => (string) $id)
                ->all()
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $repeaterData
     * @return list<ProfileSkillDTO>
     */
    private function repeaterToSkills(array $repeaterData): array
    {
        $skills = [];

        foreach ($repeaterData as $item) {
            $skillId = $item['skill_id'] ?? null;
            $proficiency = $item['proficiency'] ?? null;

            if (blank($skillId)) {
                continue;
            }

            $proficiency = $proficiency instanceof SkillProficiency
                ? $proficiency
                : SkillProficiency::tryFrom((string) $proficiency);

            // Guarda payload adulterado/inválido: sem isso, o from() no DTO lançaria
            // ValueError antes da validação graciosa do SyncProfileSkills.
            if (!$proficiency instanceof SkillProficiency) {
                continue;
            }

            $skills[] = ProfileSkillDTO::fromArray([
                'skill_id' => $skillId,
                'proficiency' => $proficiency,
                'years_experience' => $item['years_experience'] ?? null,
            ]);
        }

        return $skills;
    }
}
