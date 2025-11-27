<?php

declare(strict_types=1);

namespace He4rt\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class EventSegment extends Model
{
    use Sushi;

    protected $rows = [
        [
            'title' => 'Abertura do Evento',
        ],
        [
            'title' => 'Lançamento da Comunidade',
        ],
        [
            'title' => 'Coffee Break',
        ],
        [
            'title' => 'Fechamento do Evento',
        ],
    ];
}
