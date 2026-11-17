<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Enums\HasDescription;
use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;

enum Language: string implements HasDescription, HasLabel, HasTone
{
    case English = 'en';
    case BrazilianPortuguese = 'pt_BR';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $language): array => [$language->value => $language->getLabel()])
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::BrazilianPortuguese => 'Português (Brasil)',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::English => Tone::Info,
            self::BrazilianPortuguese => Tone::Success,
        };
    }

    public function getDescription(): string
    {
        return __(sprintf('language.%s.description', $this->value));
    }
}
