<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The meaning of a state, not its color. Each UI maps a tone to its own palette.
 */
enum Tone: string
{
    case Neutral = 'neutral';
    case Primary = 'primary';
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}
