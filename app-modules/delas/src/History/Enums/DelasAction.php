<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * O que aconteceu numa linha do histórico da He4rt Delas.
 */
enum DelasAction: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Granted = 'granted';
    case Revoked = 'revoked';
    case Blocked = 'blocked';
    case Unblocked = 'unblocked';
    case ModeratorAdded = 'moderator_added';
    case ModeratorRemoved = 'moderator_removed';

    public function getLabel(): string
    {
        return __('delas::enums.action.'.$this->value.'.label');
    }

    /**
     * Cada ação tem a sua cor para o histórico ser lido de relance. Não é uma
     * escala, então a rampa do claro ao vermelho não se aplica.
     *
     * @return array<int|string, string>
     */
    public function getColor(): array
    {
        return match ($this) {
            self::Requested => Color::Gray,
            self::Approved => Color::Green,
            self::Granted => Color::Emerald,
            self::Rejected => Color::Red,
            self::Revoked => Color::Orange,
            self::Blocked => Color::Rose,
            self::Unblocked => Color::Sky,
            self::ModeratorAdded => Color::Indigo,
            self::ModeratorRemoved => Color::Slate,
        };
    }

    public function getDescription(): string
    {
        return __('delas::enums.action.'.$this->value.'.description');
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Requested => Heroicon::OutlinedPaperAirplane,
            self::Approved => Heroicon::OutlinedCheck,
            self::Granted => Heroicon::OutlinedPlusCircle,
            self::Rejected => Heroicon::OutlinedXMark,
            self::Revoked => Heroicon::OutlinedMinusCircle,
            self::Blocked => Heroicon::OutlinedNoSymbol,
            self::Unblocked => Heroicon::OutlinedLockOpen,
            self::ModeratorAdded => Heroicon::OutlinedUserPlus,
            self::ModeratorRemoved => Heroicon::OutlinedUserMinus,
        };
    }

    /**
     * Ações que a moderação vê no histórico resumido (sem os pedidos em si).
     */
    public function isDecision(): bool
    {
        return $this !== self::Requested;
    }
}
