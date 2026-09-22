<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use He4rt\Identity\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MobileMeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'avatar_url' => $user->getFilamentAvatarUrl(),
        ]);
    }
}
