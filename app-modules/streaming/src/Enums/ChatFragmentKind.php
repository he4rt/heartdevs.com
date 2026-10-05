<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ChatFragmentKind: string implements HasColor, HasDescription, HasLabel
{
    case Text = 'text';
    case Emote = 'emote';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Emote => 'Emote',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Text => 'Trecho de texto da mensagem, incluindo menções.',
            self::Emote => 'Emote da plataforma, mostrado como imagem.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Text => 'gray',
            self::Emote => 'primary',
        };
    }
}
