<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use App\Enums\Language;
use He4rt\Identity\Users\User;

final readonly class UpdatePreferences
{
    /**
     * A null theme color goes back to the default accent.
     */
    public function handle(User $user, Language $locale, ?string $themeColor): void
    {
        $user->update(['locale' => $locale->value, 'theme_color' => $themeColor]);
    }
}
