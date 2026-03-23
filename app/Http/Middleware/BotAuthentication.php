<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BotAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-He4rt-Authorization');

        if (!$apiKey) {
            return response()->json(['error' => 'Chave não encontrada'], Response::HTTP_UNAUTHORIZED);
        }

        if ($apiKey !== config('he4rt.server_key')) {
            return response()->json(['error' => 'Chave incorreta'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
