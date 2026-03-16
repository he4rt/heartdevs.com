<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Actions\UpdateProfile;
use He4rt\Identity\User\Exceptions\ProfileException;
use He4rt\Identity\User\Http\Requests\UpdateProfileRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Http\JsonResponse;

final class UsersController extends Controller
{
    public function getUsers(): JsonResponse
    {
        return response()->json(User::query()->paginate(15));
    }

    public function getUser(string $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);

        return response()->json($user);
    }

    /**
     * @throws ProfileException
     */
    public function getProfile(string $value): JsonResponse
    {
        $user = User::query()->where('username', $value)->first();

        if (! $user) {
            $provider = ExternalIdentity::query()->where('provider_id', $value)->first();

            throw_unless($provider, ProfileException::notFound());

            $user = User::query()->findOrFail($provider->model_id);
        }

        $user->load([
            'character',
            'providers',
            'information',
            'character.badges',
            'address',
            'character.pastSeasons',
        ]);

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'character' => $user->character,
            'connectedProviders' => $user->providers,
            'badges' => $user->character?->badges ?? [],
            'address' => $user->address,
            'pastSeasons' => $user->character?->pastSeasons ?? [],
        ]);
    }

    public function putProfile(
        UpdateProfileRequest $request,
        string $value,
        UpdateProfile $action,
    ): JsonResponse {
        $action->handle($value);

        return response()->json();
    }
}
