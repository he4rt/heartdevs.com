<?php

declare(strict_types=1);

namespace App\Models\Mail;

/**
 * The email addresses of one mail header, such as To or Cc.
 */
final readonly class MailAddresses
{
    /** @param  array<int, string>  $addresses */
    private function __construct(public array $addresses) {}

    /** @param  array<array-key, mixed>  $values */
    public static function fromArray(array $values): self
    {
        return new self(collect($values)->filter(fn (mixed $value): bool => is_string($value))->values()->all());
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return $this->addresses;
    }
}
