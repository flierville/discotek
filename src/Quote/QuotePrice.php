<?php

declare(strict_types=1);

namespace App\Quote;

final readonly class QuotePrice
{
    public function __construct(
        public int $distanceKm,
        public int $servicePrice,
        public int $travelPrice,
        public string $pricingRule,
    ) {
    }

    public function total(): int
    {
        return $this->servicePrice + $this->travelPrice;
    }
}
