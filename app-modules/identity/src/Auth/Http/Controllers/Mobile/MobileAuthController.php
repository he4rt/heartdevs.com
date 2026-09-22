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

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        try {
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

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(status: 204);
    }
}
