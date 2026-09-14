<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApplicationLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetApplicationLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        ApplicationLocale::apply(ApplicationLocale::resolve());

        return $next($request);
    }
}
