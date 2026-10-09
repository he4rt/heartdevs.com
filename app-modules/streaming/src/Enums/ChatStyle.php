<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ChatStyle: string implements HasDescription, HasLabel
{
    case Lines = 'lines';
    case Bubbles = 'bubbles';
    case Panel = 'panel';
    case Spotlight = 'spotlight';
    case Terminal = 'terminal';
    case Glass = 'glass';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lines => 'Linhas',
            self::Bubbles => 'Balões',
            self::Panel => 'Painel',
            self::Spotlight => 'Destaque',
            self::Terminal => 'Terminal',
            self::Glass => 'Vidro',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Lines => 'Texto com contorno, sem fundo. Sobre jogo ou tela.',
            self::Bubbles => 'Um balão escuro por mensagem. Gameplay agitada.',
            self::Panel => 'Uma caixa escura atrás de todas as mensagens. Coluna lateral.',
            self::Spotlight => 'Fonte grande, com o nome em cima. Vídeo vertical.',
            self::Terminal => 'Janela de terminal com prompt de shell. Live de código.',
            self::Glass => 'Uma faixa clara translúcida por mensagem. Câmera e conversa.',
        };
    }
}
