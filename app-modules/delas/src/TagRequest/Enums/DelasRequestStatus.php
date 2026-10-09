<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * O estado de uma solicitação da tag He4rt Delas.
 *
 * `pending` e `approved` são ativos: no máximo um por pessoa, garantido por
 * índice único parcial. `rejected` e `revoked` são finais e abrem a espera.
 */
enum DelasRequestStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    use StringifyEnum;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->isActive()),
        ));
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('delas::enums.request_status.pending.label'),
            self::Approved => __('delas::enums.request_status.approved.label'),
            self::Rejected => __('delas::enums.request_status.rejected.label'),
            self::Revoked => __('delas::enums.request_status.revoked.label'),
        };
    }

    /**
     * Estados, não níveis: a rampa do claro ao vermelho não se aplica.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Revoked => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Pending => __('delas::enums.request_status.pending.description'),
            self::Approved => __('delas::enums.request_status.approved.description'),
            self::Rejected => __('delas::enums.request_status.rejected.description'),
            self::Revoked => __('delas::enums.request_status.revoked.description'),
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Approved => Heroicon::OutlinedCheck,
            self::Rejected => Heroicon::OutlinedXMark,
            self::Revoked => Heroicon::OutlinedMinusCircle,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Approved, self::Rejected], strict: true),
            self::Approved => $target === self::Revoked,
            self::Rejected, self::Revoked => false,
        };
    }

    /**
     * Ocupa o lugar da pessoa: enquanto existir, não dá para pedir de novo.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Approved => true,
            self::Rejected, self::Revoked => false,
        };
    }

    /**
     * Encerra a solicitação e abre a espera para um novo pedido.
     */
    public function startsCooldown(): bool
    {
        return !$this->isActive();
    }
}
