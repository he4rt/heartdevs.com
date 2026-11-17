<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Account\Profile\Data;

use He4rt\Identity\Users\User;
use Spatie\LaravelData\Data;

final class ProfileData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $locale,
        public ?string $themeColor,
        public bool $hasPassword,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            locale: $user->locale ?? config()->string('app.locale'),
            themeColor: $user->theme_color,
            hasPassword: $user->hasPassword(),
        );
    }
}
