<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<QrToken> */
final class QrTokenFactory extends Factory
{
    protected $model = QrToken::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'token' => Str::random(64),
            'expires_at' => null,
        ];
    }
}
