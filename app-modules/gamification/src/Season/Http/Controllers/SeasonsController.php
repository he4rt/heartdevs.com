<?php

declare(strict_types=1);

namespace He4rt\Gamification\Season\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Gamification\Season\Models\Season;
use Symfony\Component\HttpFoundation\JsonResponse;

final class SeasonsController extends Controller
{
    public function getSeasons(): JsonResponse
    {
        return response()->json(Season::all());
    }

    public function getCurrent(): JsonResponse
    {
        return response()->json(Season::query()->find(config('he4rt.season.id')));
    }
}
