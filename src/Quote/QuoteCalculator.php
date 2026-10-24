<?php

declare(strict_types=1);

namespace App\Quote;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Geo\GeoPoint;
use App\Repository\SpecialDateRepository;

/**
 * Computes the price of the music entertainment. Priority of the rules:
 *  1. a special date (24/12, 31/12...) fixes the price;
 *  2. otherwise an event type with a fixed price (New Year's Eve...);
 *  3. otherwise the price of the guest range, plus the bonus / malus of the event type.
 * The travel fee is then added (the email presents it as "included in the package").
 */
final readonly class QuoteCalculator
{
    public function __construct(private SpecialDateRepository $specialDates)
    {
    }

    public function calculate(
        TravelPolicy $travelPolicy,
        GuestRange $guestRange,
        ?EventType $eventType,
        \DateTimeInterface $eventDate,
        GeoPoint $destination,
    ): QuotePrice {
        [$servicePrice, $rule] = $this->servicePrice($guestRange, $eventType, $eventDate);
        $distanceKm = $travelPolicy->estimateRoadDistanceKm($destination);

        return new QuotePrice($distanceKm, $servicePrice, $travelPolicy->priceFor($distanceKm), $rule);
    }

    /** @return array{int, string} */
    private function servicePrice(GuestRange $guestRange, ?EventType $eventType, \DateTimeInterface $eventDate): array
    {
        $specialDate = $this->specialDates->findOneForDate($eventDate);
        if (null !== $specialDate) {
            return [$specialDate->getPrice(), \sprintf('Date spéciale : %s', $specialDate->getLabel())];
        }

        if (null !== $eventType?->getFixedPrice()) {
            return [$eventType->getFixedPrice(), \sprintf("Type d'événement : %s", $eventType->getLabel())];
        }

        $rule = \sprintf('Nombre de personnes : %s', $guestRange->getLabel());
        $adjustment = $eventType?->getPriceAdjustment();
        if (null === $adjustment || 0 === $adjustment) {
            return [$guestRange->getPrice(), $rule];
        }

        return [
            max(0, $guestRange->getPrice() + $adjustment),
            \sprintf("%s, type d'événement : %s (%+d €)", $rule, $eventType->getLabel(), $adjustment),
        ];
    }
}
