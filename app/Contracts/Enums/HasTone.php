<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

use App\Enums\Tone;

interface HasTone
{
    public function getTone(): Tone;
}
