<?php

declare(strict_types=1);

namespace He4rt\User\Filament\User\Pages;

use App\Livewire\ConnectionHub;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Auth\Notifications\NoticeOfEmailChangeRequest;
use Filament\Auth\Notifications\VerifyEmailChange;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Concerns\HasTopbar;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Js;
use Illuminate\Validation\Rules\Password;
use League\Uri\Components\Query;
use LogicException;
use OtavioAraujo\FilamentSmartCep\Forms\Components\SmartCep;
use Throwable;

/**
 * @property-read Schema $form
 */
class UserProfile extends Page
{
    use CanUseDatabaseTransactions;
    use HasMaxWidth;
    use HasTopbar;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public ?array $informationData = [];

    public ?array $addressData = [];

    protected static bool $isDiscovered = false;

    protected string $view;

    public static function isSimple(): bool
    {
        return true;
    }

    public static function getLabel(): string
    {
        return self::$title ?? __('filament-panels::auth/pages/edit-profile.label');
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'profile';
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return self::$slug ?? 'profile';
    }

    public function getLayout(): string
    {
        return self::$layout ?? (self::isSimple() ? 'filament-panels::components.layout.simple' : 'filament-panels::components.layout.index');
    }

    public function getView(): string
    {
        return $this->view ?? 'filament-panels::auth.pages.edit-profile';
    }

    public function mount(): void
    {
        $this->fillForm();

        $user = $this->getUser();

        $this->informationData = $user->information?->toArray() ?? [];
        $this->addressData = $user->address?->toArray() ?? [];
    }

    public function getUser(): Authenticatable&Model
    {
        $user = Filament::auth()->user();

        throw_unless($user instanceof Model, LogicException::class,
            'The authenticated user object must be an Eloquent model to allow the profile page to update it.');

        return $user;
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->getUser(), $data);

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_'.Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
        }
    }

    public function saveInformation(): void
    {
        $this->validate([
            'informationData.name' => ['required', 'string', 'max:255'],
            'informationData.nickname' => ['nullable', 'string', 'max:255'],
            'informationData.linkedin_url' => ['nullable', 'string', 'url', 'max:255'],
            'informationData.github_url' => ['nullable', 'string', 'url', 'max:255'],
            'informationData.birthdate' => ['nullable', 'date'],
            'informationData.about' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $this->getUser();

        $user->information()->updateOrCreate(
            ['user_id' => $user->id],
            $this->informationData
        );

        FilamentNotification::make()
            ->title('Information updated successfully.')
            ->success()
            ->send();
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addressData.zip_code' => [
                'required',
                'string',
                'regex:/^\d{5}-\d{3}$/',
            ],
            'addressData.country' => ['nullable', 'string', 'max:255'],
            'addressData.state' => ['nullable', 'string', 'max:255'],
            'addressData.city' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $this->getUser();

        $user->address()->updateOrCreate(
            ['user_id' => $user->id],
            $this->addressData
        );

        FilamentNotification::make()
            ->title('Address updated successfully.')
            ->success()
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(! self::isSimple())
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    public function getFormActionsAlignment(): Alignment
    {
        return Alignment::Start;
    }

    public function getMaxWidth(): Width
    {
        return Width::ScreenLarge;
    }

    public function getTitle(): string
    {
        return self::getLabel();
    }

    public function hasLogo(): bool
    {
        return false;
    }

    /**
     * @deprecated Use `getCancelFormAction()` instead.
     */
    public function backAction(): Action
    {
        $url = filament()->getUrl();

        return Action::make('back')
            ->label(__('filament-panels::auth/pages/edit-profile.actions.cancel.label'))
            ->alpineClickHandler(
                FilamentView::hasSpaMode($url)
                    ? 'document.referrer ? window.history.back() : Livewire.navigate('.Js::from($url).')'
                    : 'document.referrer ? window.history.back() : (window.location.href = '.Js::from($url).')',
            )
            ->color('gray');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                $this->getFormContentComponent(),
                            ]),
                        Tab::make('Connections')
                            ->schema([
                                Livewire::make(ConnectionHub::class),
                            ]),
                        Tab::make('Information')
                            ->schema([
                                Section::make('Personal Information')
                                    ->description('Basic profile details and social links.')
                                    ->schema([
                                        TextInput::make('informationData.name')
                                            ->label('Full Name')

                                            ->placeholder('Enter your full name')
                                            ->required(),

                                        TextInput::make('informationData.nickname')
                                            ->label('Nickname')
                                            ->placeholder('How do you like to be called?'),

                                        DatePicker::make('informationData.birthdate')
                                            ->label('Birthdate')
                                            ->placeholder('Select your birth date'),

                                        Textarea::make('informationData.about')
                                            ->label('About')
                                            ->placeholder('Write a short description about yourself...')
                                            ->rows(4)
                                            ->columnSpanFull(),

                                        TextInput::make('informationData.linkedin_url')
                                            ->label('LinkedIn URL')
                                            ->placeholder('https://linkedin.com/in/username')
                                            ->url(),

                                        TextInput::make('informationData.github_url')
                                            ->label('GitHub URL')
                                            ->placeholder('https://github.com/username')
                                            ->url(),
                                    ])
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 3,
                                    ])
                                    ->footerActions([
                                        Action::make('saveInformation')
                                            ->label('Save Information')
                                            ->action(fn () => $this->saveInformation())
                                            ->color('primary')
                                            ->icon('heroicon-o-check'),
                                    ]),
                            ]),

                        Tab::make('Address')
                            ->schema([
                                Section::make('Address Information')
                                    ->description('Fill in your current address. The ZIP Code will automatically fetch your city and state.')
                                    ->schema([
                                        SmartCep::make('addressData.zip_code')
                                            ->label('ZIP Code')
                                            ->placeholder('Enter your ZIP Code (e.g., 13000-000)')
                                            ->mask('99999-999')
                                            ->required()
                                            ->bindCityField('addressData.city')
                                            ->bindStateField('addressData.state')
                                            ->bindCountryField('addressData.country')
                                            ->live()
                                            ->columnSpan(1),

                                        TextInput::make('addressData.country')
                                            ->label('Country')
                                            ->placeholder('Brazil')
                                            ->columnSpan(1),

                                        TextInput::make('addressData.state')
                                            ->label('State')
                                            ->placeholder('São Paulo')
                                            ->columnSpan(1),

                                        TextInput::make('addressData.city')
                                            ->label('City')
                                            ->placeholder('Campinas')
                                            ->columnSpan(1),
                                    ])
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 4,
                                    ])
                                    ->footerActions([
                                        Action::make('saveAddress')
                                            ->label('Save Address')
                                            ->action(fn () => $this->saveAddress())
                                            ->color('primary')
                                            ->icon('heroicon-o-map-pin'),
                                    ]),
                            ]),
                    ]),
                ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
            ]);
    }

    public function getFormContentComponent(): Form
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->sticky((! self::isSimple()) && $this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        if (! Filament::hasMultiFactorAuthentication()) {
            return null;
        }

        $user = Filament::auth()->user();

        return Section::make()
            ->label(__('filament-panels::auth/pages/edit-profile.multi_factor_authentication.label'))
            ->compact()
            ->divided()
            ->secondary()
            ->schema(collect(Filament::getMultiFactorAuthenticationProviders())
                ->sort(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider
                ): int => $multiFactorAuthenticationProvider->isEnabled($user) ? 0 : 1)
                ->map(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider
                ): Component => Group::make($multiFactorAuthenticationProvider->getManagementSchemaComponents())
                    ->statePath($multiFactorAuthenticationProvider->getId()))
                ->all());
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
            'maxContentWidth' => $maxContentWidth = $this->getMaxWidth() ?? $this->getMaxContentWidth(),
            'maxWidth' => $maxContentWidth,
        ];
    }

    private function fillForm(): void
    {
        $data = $this->getUser()->attributesToArray();

        $this->callHook('beforeFill');

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleRecordUpdate(Model $record, array $data): Model
    {
        if (Filament::hasEmailChangeVerification() && array_key_exists('email', $data)) {
            $this->sendEmailChangeVerification($record, $data['email']);

            unset($data['email']);
        }

        $record->update($data);

        return $record;
    }

    private function sendEmailChangeVerification(Model $record, string $newEmail): void
    {
        if ($record->getAttributeValue('email') === $newEmail) {
            return;
        }

        $notification = app(VerifyEmailChange::class);
        $notification->url = Filament::getVerifyEmailChangeUrl($record, $newEmail);

        $verificationSignature = Query::new($notification->url)->get('signature');

        cache()->put($verificationSignature, true, ttl: now()->addHour());

        $record->notify(app(NoticeOfEmailChangeRequest::class, [
            /** @phpstan-ignore-line */
            'blockVerificationUrl' => Filament::getBlockEmailChangeVerificationUrl($record, $newEmail,
                $verificationSignature),
            'newEmail' => $newEmail,
        ]));

        Notification::route('mail', $newEmail)
            ->notify($notification);

        $this->getEmailChangeVerificationSentNotification($newEmail)->send();

        $this->data['email'] = $record->getAttributeValue('email');
    }

    private function getSavedNotification(): ?FilamentNotification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return FilamentNotification::make()
            ->success()
            ->title($title);
    }

    private function getEmailChangeVerificationSentNotification(string $newEmail): FilamentNotification
    {
        return FilamentNotification::make()
            ->success()
            ->title(__('filament-panels::auth/pages/edit-profile.notifications.email_change_verification_sent.title',
                ['email' => $newEmail]))
            ->body(__('filament-panels::auth/pages/edit-profile.notifications.email_change_verification_sent.body',
                ['email' => $newEmail]));
    }

    private function getSavedNotificationTitle(): ?string
    {
        return __('filament-panels::auth/pages/edit-profile.notifications.saved.title');
    }

    private function getRedirectUrl(): ?string
    {
        return null;
    }

    private function getNameFormComponent(): TextInput
    {
        return TextInput::make('name')
            ->label(__('filament-panels::auth/pages/edit-profile.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    private function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/edit-profile.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    private function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/edit-profile.form.password.label'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password.validation_attribute'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn ($state): bool => filled($state))
            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    private function getPasswordConfirmationFormComponent(): TextInput
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::auth/pages/edit-profile.form.password_confirmation.label'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password_confirmation.validation_attribute'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    private function getCurrentPasswordFormComponent(): TextInput
    {
        return TextInput::make('currentPassword')
            ->label(__('filament-panels::auth/pages/edit-profile.form.current_password.label'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.current_password.validation_attribute'))
            ->belowContent(__('filament-panels::auth/pages/edit-profile.form.current_password.below_content'))
            ->password()
            ->autocomplete('current-password')
            ->currentPassword(guard: Filament::getAuthGuard())
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get
            ): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    private function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    private function getCancelFormAction(): Action
    {
        return $this->backAction();
    }

    private function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::auth/pages/edit-profile.form.actions.save.label'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    private function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
