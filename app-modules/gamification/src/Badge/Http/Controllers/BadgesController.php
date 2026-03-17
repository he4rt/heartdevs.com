<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Gamification\Badge\Actions\CreateBadge;
use He4rt\Gamification\Badge\Actions\DeleteBadge;
use He4rt\Gamification\Badge\Http\Requests\CreateBadgeRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class BadgesController extends Controller
{
    public function postBadge(
        CreateBadgeRequest $request,
        CreateBadge $persistBadge
    ): JsonResponse {
        return response()->json(
            $persistBadge->handle($request->validated()),
            Response::HTTP_CREATED
        );
    }

    public function deleteBadge(int $badgeId, DeleteBadge $deleteBadge): Response
    {
        $deleteBadge->handle($badgeId);

        return response()->noContent();
    }
}
