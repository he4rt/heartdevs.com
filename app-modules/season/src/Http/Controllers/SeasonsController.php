<?php

declare(strict_types=1);

namespace He4rt\Season\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Season\Actions\GetCurrentSeason;
use He4rt\Season\Actions\GetSeasons;
use Symfony\Component\HttpFoundation\JsonResponse;

final class SeasonsController extends Controller
{
    public function getSeasons(GetSeasons $getSeasons): JsonResponse
    {
        return response()->json($getSeasons->handle());
    }

    public function getCurrent(GetCurrentSeason $currentSeason): JsonResponse
    {
        return response()->json($currentSeason->handle());
    }
}
