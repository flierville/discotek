<?php

declare(strict_types=1);

namespace App\Quote;

use App\Entity\Settings;
use App\Geo\GeoPoint;

/**
 * Travel pricing rules of the legacy site:
 *  - road distance = bird flight distance x road factor (1.22), rounded to the km;
 *  - up to 20 km: 20 EUR flat fee;
 *  - beyond: 40 EUR + 1 EUR per km above 20 km.
 */
final readonly class TravelPolicy
{
    public function __construct(
        public GeoPoint $departure,
        public float $roadFactor,
        public int $shortTripMaxKm,
        public int $shortTripFee,
        public int $longTripBaseFee,
        public float $longTripPricePerKm,
    ) {
    }

    public static function fromSettings(Settings $settings): self
    {
        return new self(
            new GeoPoint($settings->getDepartureLatitude(), $settings->getDepartureLongitude()),
            $settings->getRoadFactor(),
            $settings->getShortTripMaxKm(),
            $settings->getShortTripFee(),
            $settings->getLongTripBaseFee(),
            $settings->getLongTripPricePerKm(),
        );
    }

    public function estimateRoadDistanceKm(GeoPoint $destination): int
    {
        return (int) round($this->departure->distanceTo($destination) * $this->roadFactor);
    }

    public function priceFor(int $distanceKm): int
    {
        if ($distanceKm <= $this->shortTripMaxKm) {
            return $this->shortTripFee;
        }

        return $this->longTripBaseFee + (int) round(($distanceKm - $this->shortTripMaxKm) * $this->longTripPricePerKm);
    }
}
