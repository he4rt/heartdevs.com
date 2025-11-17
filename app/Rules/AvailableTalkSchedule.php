<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Support\Facades\Date;
use Illuminate\Translation\PotentiallyTranslatedString;
use Closure;
use He4rt\Events\Models\EventModel;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableTalkSchedule implements ValidationRule
{
    public function __construct(
        private readonly string|int $eventId,
        private readonly string $start_at,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = Date::parse($this->start_at)->toDateTimeString();
        $end = Date::parse($value)->toDateTimeString();

        $isAvailable = EventModel::query()
            ->where('id', $this->eventId)
            ->availableHours(start: $start, end: $end)
            ->exists();

        if (! $isAvailable) {
            $fail(sprintf('O Horário %s até %s não está disponível.', $end, $start));
        }
    }
}
