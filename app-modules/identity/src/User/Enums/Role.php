<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Role: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case Staff = 'staff';
    case Compliance = 'compliance';
    case Recruiter = 'recruiter';
    case SquadCaptain = 'squad_captain';
    case Member = 'member';

    public function getLabel(): string
    {
        return __('identity::enums.role.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Staff => Color::Amber,
            self::Compliance => Color::Red,
            self::Recruiter => Color::Blue,
            self::SquadCaptain => Color::Purple,
            self::Member => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Staff => Heroicon::ShieldCheck,
            self::Compliance => Heroicon::Scale,
            self::Recruiter => Heroicon::Briefcase,
            self::SquadCaptain => Heroicon::Flag,
            self::Member => Heroicon::User,
        };
    }

    /**
     * Compliance é hierarquicamente um superconjunto de Staff: quem pode
     * fazer hard delete também pode editar/soft-deletar.
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::Staff, self::Compliance], strict: true);
    }

    public function isCompliance(): bool
    {
        return $this === self::Compliance;
    }

    /**
     * Quem enxerga a ficha de um membro no painel: staff/compliance (gestão)
     * e recruiter/squad captain (recrutamento), mas não member.
     */
    public function canViewUsers(): bool
    {
        return in_array($this, [self::Staff, self::Compliance, self::Recruiter, self::SquadCaptain], strict: true);
    }
}
