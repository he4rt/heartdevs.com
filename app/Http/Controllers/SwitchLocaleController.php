<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\ApplicationLocale;
use Illuminate\Http\RedirectResponse;

final class SwitchLocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(ApplicationLocale::isSupported($locale), 404);

        session([ApplicationLocale::SESSION_KEY => $locale]);

        return back();
    }
}
