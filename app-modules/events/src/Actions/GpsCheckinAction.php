<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use Exception;
use He4rt\Events\Enums\CheckinStatusEnum;
use He4rt\Events\Enums\EventStatusEnum;
use He4rt\Events\Models\EventModel;
use Illuminate\Http\JsonResponse;

final readonly class GpsCheckinAction
{
    public function __construct(
        private HaversineAction $haversineAction,
        private XpCalculatorAction $xpCalculatorAction,
        private UpdateCheckinAction $updateCheckinAction,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(EventModel $eventModel, float $latitude, float $longitude): JsonResponse
    {
        $pivot = $eventModel->attendees()->where('user_id', auth()->user()->id)->first()?->pivot;

        if (!$pivot) {
            return response()->json(['error' => 'not_registered'], 403);
        }

        if ($eventModel->status !== EventStatusEnum::Scheduled) {
            return response()->json(['error' => 'event_not_scheduled'], 400);
        }

        if (now()->lt($eventModel->start_at->copy()->subMinutes(30))) {
            return response()->json(['error' => 'event_not_started'], 400);
        }

        if (now()->gt($eventModel->end_at->copy()->addMinutes(30))) {
            return response()->json(['error' => 'event_already_finished'], 400);
        }

        if ($pivot->state === CheckinStatusEnum::Verified) {
            return response()->json([
                'state' => $pivot->state,
                'verified_at' => $pivot->verified_at,
                'xp_awarded' => $pivot->xp_awarded,
            ], 409);
        }

        $distanceFinalInMeters = $this->haversineAction->execute(
            $latitude,
            $longitude,
            $eventModel->location_lat,
            $eventModel->location_lng
        );

        if ($distanceFinalInMeters > $eventModel->gps_radius) {
            return response()->json([
                'error' => 'outside_radius',
                'distance_meters' => round($distanceFinalInMeters),
                'radius_meters' => $eventModel->gps_radius,
            ], 400);
        }

        $xpAwarded = $this->xpCalculatorAction->execute($eventModel);

        $this->updateCheckinAction->execute($pivot, $xpAwarded);

        // Soma XP ao perfil geral do usuário
        auth()->user()->character->increment('experience', $xpAwarded);

        return response()->json([
            'state' => CheckinStatusEnum::Verified,
            'verification_method' => 'gps',
            'verified_at' => $pivot->fresh()->verified_at->toISOString(),
            'xp_awarded' => $xpAwarded,
            // TODO: HE4-47 - chamar action de streak aqui
            'streak_multiplier' => 1, // fixo até HE4-47
            'streak_current' => 0, // fixo até HE4-47
        ]);
    }
}
