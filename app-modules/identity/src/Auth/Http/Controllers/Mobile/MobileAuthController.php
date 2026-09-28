<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use He4rt\Identity\Auth\Actions\ExchangeMobileCodeAction;
use He4rt\Identity\Auth\Actions\IssueMobileTokenAction;
use He4rt\Identity\Auth\DTOs\MobileTokenDTO;
use He4rt\Identity\Auth\Exceptions\MobileAuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final class MobileAuthController extends Controller
{
    /**
     * Trocar código de login por token
     *
     * Troca o código de uso único devolvido no deep link do OAuth (ver
     * MobileOAuthController::redirect) por um par de token de acesso JWT.
     * O código expira em 60s e só pode ser usado uma vez.
     */
    public function exchange(Request $request, ExchangeMobileCodeAction $exchangeCode, IssueMobileTokenAction $issueToken): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $user = $exchangeCode->execute($request->string('code')->toString());
        } catch (MobileAuthException $mobileAuthException) {
            return response()->json(['message' => $mobileAuthException->getMessage()], 401);
        }

        return response()->json($issueToken->execute($user)->toArray());
    }

    /**
     * Renovar token de acesso
     *
     * Emite um novo token a partir do token atual do header Authorization,
     * mesmo que já tenha expirado — desde que dentro da janela de refresh
     * (jwt.refresh_ttl) e não esteja na blacklist.
     */
    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        try {
            /** @var string $token */
            $token = $guard->refresh();
        } catch (JWTException $jwtException) {
            return response()->json(['message' => $jwtException->getMessage()], 401);
        }

        $refreshed = new MobileTokenDTO(
            accessToken: $token,
            tokenType: 'bearer',
            expiresIn: config()->integer('jwt.ttl') * 60,
        );

        return response()->json($refreshed->toArray());
    }

    /**
     * Encerrar sessão
     *
     * Invalida o token de acesso atual (blacklist) — o mesmo token não
     * autentica nem renova depois disso.
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(status: 204);
    }
}
