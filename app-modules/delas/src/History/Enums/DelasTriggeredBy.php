<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;

/**
 * Em que papel a pessoa agiu quando a linha do histórico foi gravada.
 *
 * Fica gravado porque o papel muda com o tempo: quem é líder hoje pode ter
 * decidido como moderadora.
 */
enum DelasTriggeredBy: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case User = 'user';
    case Moderator = 'moderator';
    case Lead = 'lead';
    case Admin = 'admin';
    case System = 'system';

    public static function for(User $actor): self
    {
        return match (true) {
            $actor->isSuperAdmin() => self::Admin,
            $actor->hasRole(UserRole::DelasLead) => self::Lead,
            $actor->hasRole(UserRole::DelasModerator) => self::Moderator,
            default => self::User,
        };
    }

    public function getLabel(): string
    {
        return __('delas::enums.triggered_by.'.$this->value.'.label');
    }

    public function getColor(): string
    {
        return match ($this) {
            self::User, self::System => 'gray',
            self::Moderator => 'info',
            self::Lead => 'primary',
            self::Admin => 'danger',
        };
    }

    public function getDescription(): string
    {
        return __('delas::enums.triggered_by.'.$this->value.'.description');
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::User => Heroicon::OutlinedUser,
            self::Moderator => Heroicon::OutlinedHeart,
            self::Lead => Heroicon::OutlinedSparkles,
            self::Admin => Heroicon::OutlinedShieldCheck,
            self::System => Heroicon::OutlinedCog6Tooth,
        };
    }
}
