<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use BackedEnum;
use UnitEnum;

trait StringifyEnum
{
    public static function stringifyCases(string $context = 'Available enum cases'): string
    {
        $cases = collect(self::cases())
            ->implode(fn (BackedEnum|UnitEnum $enum) => $enum->value, ', ');

        return sprintf('%s: %s', $context, $cases);
    }
}
