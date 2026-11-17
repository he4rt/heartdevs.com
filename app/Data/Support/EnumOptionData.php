<?php

declare(strict_types=1);

namespace App\Data\Support;

use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;
use BackedEnum;
use Spatie\LaravelData\Data;

final class EnumOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        public Tone $tone,
    ) {}

    public static function fromEnum(BackedEnum&HasLabel $case): self
    {
        return new self(
            value: (string) $case->value,
            label: $case->getLabel(),
            tone: $case instanceof HasTone ? $case->getTone() : Tone::Neutral,
        );
    }

    /**
     * @param  class-string<BackedEnum&HasLabel>  $enum
     * @return array<int, self>
     */
    public static function options(string $enum): array
    {
        return collect($enum::cases())->map(fn (BackedEnum&HasLabel $case): self => self::fromEnum($case))->values()->all();
    }
}
