<?php

declare(strict_types=1);

namespace App\Models\Mail\Casts;

use App\Models\Mail\MailAddresses;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<MailAddresses, MailAddresses|array<array-key, mixed>>
 */
final class AsMailAddresses implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): MailAddresses
    {
        $payload = json_decode((string) ($value ?? '[]'), associative: true);

        return MailAddresses::fromArray(is_array($payload) ? $payload : []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $addresses = match (true) {
            $value instanceof MailAddresses => $value,
            is_array($value) => MailAddresses::fromArray($value),
            default => MailAddresses::fromArray([]),
        };

        return json_encode($addresses->toArray(), JSON_THROW_ON_ERROR);
    }
}
