<?php

declare(strict_types=1);

namespace App\Contracts\Enums;

interface HasLabel
{
    public function getLabel(): string;
}
