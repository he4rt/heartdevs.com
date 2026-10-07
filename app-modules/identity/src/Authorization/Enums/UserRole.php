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
    case Streamer = 'streamer';
    case DelasModerator = 'delas-moderator';
    case DelasLead = 'delas-lead';

    public const string GUARD = 'web';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Streamer => 'Streamer',
            self::DelasModerator => 'Moderadora He4rt Delas',
            self::DelasLead => 'Líder He4rt Delas',
        };
    }

    /**
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::SuperAdmin => Color::Red,
            self::Streamer => Color::Purple,
            self::DelasModerator => Color::hex('#F485A2'),
            self::DelasLead => Color::hex('#CF6481'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Acesso total ao painel admin. Passa por cima de qualquer verificação de permissão.',
            self::Streamer => 'Conecta a Twitch com permissões de broadcaster para usar as ferramentas de live.',
            self::DelasModerator => 'Aprova, rejeita e bloqueia solicitações da tag He4rt Delas no Hub.',
            self::DelasLead => 'Tudo da moderadora, mais gerenciar moderadoras e conceder ou remover a tag He4rt Delas.',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::SuperAdmin => Heroicon::OutlinedShieldCheck,
            self::Streamer => Heroicon::OutlinedVideoCamera,
            self::DelasModerator => Heroicon::OutlinedHeart,
            self::DelasLead => Heroicon::OutlinedSparkles,
        };
    }
}
