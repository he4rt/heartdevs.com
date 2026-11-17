<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Settings\Http\Requests;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\Rules\ConfiguredSocialProvider;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\DTOs\AuthenticationMethodsData;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAuthenticationSettingsRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can(PermissionsEnum::Update->buildPermissionFor(AuthenticationSettings::class));
    }

    /** @return array<string, mixed> */
    public function rules(AvailableSocialProviders $providers): array
    {
        return [
            'twoFactorEnabled' => ['required', 'boolean'],
            'passkeysEnabled' => ['required', 'boolean'],
            'socialProviders' => ['present', 'array'],
            'socialProviders.*' => ['required', 'string', 'distinct', Rule::enum(SocialProvider::class), new ConfiguredSocialProvider($providers)],
        ];
    }

    public function toDto(): AuthenticationMethodsData
    {
        return new AuthenticationMethodsData(
            twoFactorEnabled: $this->safe()->boolean('twoFactorEnabled'),
            passkeysEnabled: $this->safe()->boolean('passkeysEnabled'),
            socialProviders: $this->safe()->collect('socialProviders')
                ->map(fn (string $value): SocialProvider => SocialProvider::from($value))
                ->values()
                ->all(),
        );
    }
}
