<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Contracts\Enums\HasDescription;
use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

enum FeatureScope: string implements HasDescription, HasLabel, HasTone
{
    case Global = 'global';
    case Team = 'team';
    case User = 'user';

    public static function of(Team|User $scope): self
    {
        return $scope instanceof Team ? self::Team : self::User;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Global => 'Global',
            self::Team => 'Team',
            self::User => 'User',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::Global => Tone::Warning,
            self::Team => Tone::Primary,
            self::User => Tone::Info,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Global => 'The gate turns it on and off for everyone',
            self::Team => 'Turned on team by team',
            self::User => 'Turned on user by user',
        };
    }
}
