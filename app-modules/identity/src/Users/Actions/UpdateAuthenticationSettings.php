<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\DTOs\AuthenticationMethodsData;

/**
 * A settings group is not a model, so the activity log has no subject. The log name points at the group.
 */
final readonly class UpdateAuthenticationSettings
{
    public function __construct(private AuthenticationSettings $settings) {}

    public function handle(AuthenticationMethodsData $data): void
    {
        $old = $this->settings->toArray();

        $this->settings->fill($data->toDatabase())->save();

        activity(AuthenticationSettings::class)
            ->event('settings_updated')
            ->withProperties(['attributes' => $this->settings->toArray(), 'old' => $old])
            ->log('Authentication settings updated');
    }
}
