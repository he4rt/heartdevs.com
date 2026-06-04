<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Actions;

use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Support\Str;

final readonly class GenerateQrTokenAction
{
    public function handle(Enrollment $enrollment): QrToken
    {
        $existing = QrToken::query()->where('enrollment_id', $enrollment->getKey())->first();

        if ($existing !== null) {
            return $existing;
        }

        return QrToken::query()->create([
            'enrollment_id' => $enrollment->getKey(),
            'token' => Str::uuid7()->toString(),
        ]);
    }
}
