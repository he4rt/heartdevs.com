<?php

declare(strict_types=1);

namespace He4rt\Identity\Authorization\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Papéis atribuíveis a um usuário pelo painel admin.
 *
 * O nome do case é o `name` da role no spatie/laravel-permission. Quem tem
 * super admin passa por cima de qualquer verificação de permissão via
 * `Gate::before` em {@see \He4rt\Identity\IdentityServiceProvider}.
 */
enum UserRole: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case SuperAdmin = 'super-admin';
    case Staff = 'staff';
    case Compliance = 'compliance';
    case Recruiter = 'recruiter';
    case SquadCaptain = 'squad_captain';

    public const string GUARD = 'web';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Staff => 'Staff',
            self::Compliance => 'Compliance',
            self::Recruiter => 'Recrutador',
            self::SquadCaptain => 'Capitão de squad',
        };
    }

    /**
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::SuperAdmin => Color::Red,
            self::Staff => Color::Amber,
            self::Compliance => Color::Orange,
            self::Recruiter => Color::Blue,
            self::SquadCaptain => Color::Purple,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Acesso total ao painel admin. Passa por cima de qualquer verificação de permissão.',
            self::Staff => 'Gerencia usuários: edita identidade, perfil e endereço, e pode soft-deletar.',
            self::Compliance => 'Acumula as permissões de Staff e é o único papel que pode excluir um usuário permanentemente.',
            self::Recruiter => 'Vê a ficha de um membro para fins de recrutamento, sem acesso a moderação.',
            self::SquadCaptain => 'Vê a ficha de um membro do squad, sem acesso a moderação.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SuperAdmin => Heroicon::OutlinedShieldCheck,
            self::Staff => Heroicon::OutlinedIdentification,
            self::Compliance => Heroicon::OutlinedScale,
            self::Recruiter => Heroicon::OutlinedBriefcase,
            self::SquadCaptain => Heroicon::OutlinedFlag,
        };
    }
}
