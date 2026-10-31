<?php

declare(strict_types=1);

namespace He4rt\IntegrationSlido\Data;

final class SlidoEventHash
{
    private const string HASH_PATTERN = '[A-Za-z0-9]{6,64}';

    public static function fromInput(string $input): ?string
    {
        $input = mb_trim($input);

        if (preg_match('#^'.self::HASH_PATTERN.'$#', $input) === 1) {
            return $input;
        }

        $isSlidoEventUrl = preg_match('#^https://(?:app|wall|admin)\.sli\.do/event/('.self::HASH_PATTERN.')(?:[/?\#]|$)#', $input, $matches) === 1;

        return $isSlidoEventUrl ? $matches[1] : null;
    }
}
