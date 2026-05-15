<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

final class HaversineAction
{
    public function execute(
        float $latitudeUser,
        float $longitudeUser,
        float $latitudeEvent,
        float $longitudeEvent
    ): float {
        $earthRadius = 6371000; // In meters

        $distanceLatitude = deg2rad($latitudeEvent - $latitudeUser);
        $distanceLongitude = deg2rad($longitudeEvent - $longitudeUser);

        $a = sin($distanceLatitude / 2)
            ** 2 + cos(deg2rad($latitudeUser))
            * cos(deg2rad($latitudeEvent))
            * sin($distanceLongitude / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
