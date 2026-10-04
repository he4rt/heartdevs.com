<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ChatReader: string implements HasColor, HasDescription, HasLabel
{
    case OwnAccount = 'own_account';
    case He4rtBot = 'he4rt_bot';

    public function getLabel(): string
    {
        return match ($this) {
            self::OwnAccount => 'Lido pela minha conta',
            self::He4rtBot => 'Lido pela he4rtdevs',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::OwnAccount => 'A sua conta lê o chat. Nenhuma outra conta entra no seu canal.',
            self::He4rtBot => 'A conta bot da He4rt lê o chat do seu canal.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OwnAccount => 'primary',
            self::He4rtBot => 'info',
        };
    }
}
