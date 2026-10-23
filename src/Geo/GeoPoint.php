<?php

declare(strict_types=1);

namespace App\Geo;

final readonly class GeoPoint
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function __construct(public float $latitude, public float $longitude)
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException(\sprintf('Invalid coordinates "%s,%s".', $latitude, $longitude));
        }
    }

    /**
     * Bird flight distance (haversine formula).
     */
    public function distanceTo(self $other): float
    {
        $latFrom = deg2rad($this->latitude);
        $latTo = deg2rad($other->latitude);
        $deltaLat = $latTo - $latFrom;
        $deltaLng = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
