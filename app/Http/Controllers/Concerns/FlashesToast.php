<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Enums\ToastType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

trait FlashesToast
{
    private function toast(string $message, ToastType $type = ToastType::Success, ?string $description = null): void
    {
        Inertia::flash('toast', collect([
            'type' => $type->value,
            'message' => $message,
            'description' => $description,
        ])->filter()->all());
    }

    private function back(string $message, ToastType $type = ToastType::Success, ?string $description = null): RedirectResponse
    {
        $this->toast($message, $type, $description);

        return back();
    }
}
