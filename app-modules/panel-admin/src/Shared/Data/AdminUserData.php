<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Shared\Data;

use He4rt\Identity\Users\User;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

final class AdminUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $initials,
        public ?string $themeColor,
        public string $locale,
    ) {}

    public static function fromModel(User $user): self
    {
        $initials = Str::of($user->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            initials: $initials,
            themeColor: $user->theme_color,
            locale: $user->locale ?? config()->string('app.locale'),
        );
    }
}
