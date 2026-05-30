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
        $existing = QrToken::query()->where('enrollment_id', $enrollment->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        do {
            $token = Str::random(64);
        } while (QrToken::query()->where('token', $token)->exists());

        return QrToken::query()->create([
            'enrollment_id' => $enrollment->id,
            'token' => $token,
        ]);
    }
}
