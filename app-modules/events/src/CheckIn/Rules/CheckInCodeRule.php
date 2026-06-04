<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Rules;

use Closure;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use Illuminate\Contracts\Validation\ValidationRule;

final class CheckInCodeRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^(?:\d{4}|\d{6})$/', (string) $value)) {
            $fail(CheckInException::invalidCheckInCodeFormat()->getMessage());
        }
    }
}
