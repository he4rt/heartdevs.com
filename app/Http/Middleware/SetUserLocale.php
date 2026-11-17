<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Language;
use Closure;
use He4rt\Identity\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetUserLocale
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $language = $user instanceof User ? Language::tryFrom((string) $user->locale) : null;

        if ($language instanceof Language) {
            App::setLocale($language->value);
        }

        return $next($request);
    }
}
