<?php

declare(strict_types=1);

namespace App\Geo;

/**
 * A city chosen by a visitor, with its coordinates.
 */
final readonly class Location
{
    public function __construct(
        public string $city,
        public ?string $postalCode,
        public GeoPoint $point,
    ) {
    }
}
