<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum HealthStatus: string implements HasColor, HasIcon, HasLabel
{
    case Ok = 'ok';
    case Waiting = 'waiting';
    case Warning = 'warning';
    case Error = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ok => 'Ok',
            self::Waiting => 'Aguardando',
            self::Warning => 'Atenção',
            self::Error => 'Erro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Waiting => 'info',
            self::Warning => 'warning',
            self::Error => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Ok => Heroicon::CheckCircle,
            self::Waiting => Heroicon::Clock,
            self::Warning => Heroicon::ExclamationTriangle,
            self::Error => Heroicon::XCircle,
        };
    }

    public function needsAttention(): bool
    {
        return $this === self::Warning || $this === self::Error;
    }
}
