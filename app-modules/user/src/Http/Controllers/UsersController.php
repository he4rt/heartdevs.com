<?php

declare(strict_types=1);

namespace He4rt\User\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\User\Actions\FindProfile;
use He4rt\User\Actions\GetUser;
use He4rt\User\Actions\GetUsersPaginated;
use He4rt\User\Exceptions\ProfileException;
use He4rt\User\Exceptions\UserEntityException;
use He4rt\User\Http\Requests\UpdateProfileRequest;
use He4rt\User\Services\UpdateProfileService;
use Illuminate\Http\JsonResponse;

final class UsersController extends Controller
{
    public function getUsers(GetUsersPaginated $getUsers): JsonResponse
    {
        return response()->json($getUsers->handle());
    }

    public function getUser(int $id, GetUser $getUser): JsonResponse
    {
        try {
            return response()->json($getUser->handle($id));
        } catch (UserEntityException $userEntityException) {
            return response()->json(
                ['error' => $userEntityException->getMessage()],
                $userEntityException->getCode()
            );
        }
    }

    /**
     * @throws ProfileException
     */
    public function getProfile(string $value, FindProfile $profile): JsonResponse
    {
        return response()->json($profile->handle($value));
    }

    public function putProfile(
        UpdateProfileRequest $request,
        string $value,
        UpdateProfileService $action,
    ): JsonResponse {
        $action->handle($value); // TODO: handle need UpdateProfileDTO

        return response()->json();
    }
}
