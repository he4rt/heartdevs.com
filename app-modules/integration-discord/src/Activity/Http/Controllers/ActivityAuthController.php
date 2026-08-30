<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Activity\Http\Controllers;

use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Activity\Actions\AuthenticateActivityUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Troca o `code` do SDK da Discord Activity por sessão autenticada, quando a conta já está vinculada. */
final class ActivityAuthController
{
    public function __invoke(Request $request, AuthenticateActivityUser $action): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $result = $action->execute($request->string('code')->toString());

        if ($result->user instanceof User) {
            auth()->login($result->user);
        }

        return response()->json([
            'linked' => $result->user instanceof User,
            'access_token' => $result->accessToken,
        ]);
    }
}
