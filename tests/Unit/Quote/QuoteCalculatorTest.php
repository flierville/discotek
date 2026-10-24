<?php

declare(strict_types=1);

namespace App\Tests\Unit\Quote;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Entity\SpecialDate;
use App\Enum\EventPricing;
use App\Geo\GeoPoint;
use App\Quote\QuoteCalculator;
use App\Quote\TravelPolicy;
use App\Repository\SpecialDateRepository;
use PHPUnit\Framework\TestCase;

final class QuoteCalculatorTest extends TestCase
{
    private const GRUGNY = [49.615395, 1.103135];

    private TravelPolicy $travelPolicy;
    private GuestRange $guestRange;

    protected function setUp(): void
    {
        $this->travelPolicy = new TravelPolicy(new GeoPoint(...self::GRUGNY), 1.22, 20, 20, 40, 1.0);
        $this->guestRange = new GuestRange('Entre 80 et 135 personnes', 730);
    }

    public function testPriceOfTheGuestRangePlusTravel(): void
    {
        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, new EventType('Mariage'), new \DateTimeImmutable('2027-06-12'), new GeoPoint(...self::GRUGNY));

        self::assertSame(730, $price->servicePrice);
        self::assertSame(0, $price->distanceKm);
        self::assertSame(20, $price->travelPrice);
        self::assertSame(750, $price->total());
        self::assertSame('Nombre de personnes : Entre 80 et 135 personnes', $price->pricingRule);
    }

    public function testEventTypeWithAFixedPriceReplacesTheGuestRangePrice(): void
    {
        $newYear = new EventType("Réveillon du jour de l'an", 1000);

        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, $newYear, new \DateTimeImmutable('2027-01-15'), new GeoPoint(...self::GRUGNY));

        self::assertSame(1000, $price->servicePrice);
        self::assertSame("Type d'événement : Réveillon du jour de l'an", $price->pricingRule);
    }

    public function testEventTypeBonusIsAddedToTheGuestRangePrice(): void
    {
        $wedding = (new EventType('Mariage'))->setPricing(EventPricing::Adjustment)->setPriceAdjustment(70);

        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, $wedding, new \DateTimeImmutable('2027-06-12'), new GeoPoint(...self::GRUGNY));

        self::assertSame(800, $price->servicePrice);
        self::assertSame("Nombre de personnes : Entre 80 et 135 personnes, type d'événement : Mariage (+70 €)", $price->pricingRule);
    }

    public function testEventTypeMalusIsRemovedFromTheGuestRangePrice(): void
    {
        $birthday = (new EventType('Anniversaire'))->setPricing(EventPricing::Adjustment)->setPriceAdjustment(-100);

        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, $birthday, new \DateTimeImmutable('2027-06-12'), new GeoPoint(...self::GRUGNY));

        self::assertSame(630, $price->servicePrice);
        self::assertSame("Nombre de personnes : Entre 80 et 135 personnes, type d'événement : Anniversaire (-100 €)", $price->pricingRule);
    }

    public function testValueOfTheInactivePricingIsIgnored(): void
    {
        $party = (new EventType('Soirée', 1500))->setPricing(EventPricing::Adjustment)->setPriceAdjustment(50);

        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, $party, new \DateTimeImmutable('2027-06-12'), new GeoPoint(...self::GRUGNY));

        self::assertSame(780, $price->servicePrice);
    }

    public function testSpecialDateHasPriorityOverEverything(): void
    {
        $specialDate = new SpecialDate('Réveillon de la Saint-Sylvestre', 31, 12, 1000);
        $calculator = $this->calculator($specialDate);

        $price = $calculator->calculate($this->travelPolicy, $this->guestRange, new EventType('Gala', 1500), new \DateTimeImmutable('2027-12-31'), new GeoPoint(...self::GRUGNY));

        self::assertSame(1000, $price->servicePrice);
        self::assertSame('Date spéciale : Réveillon de la Saint-Sylvestre', $price->pricingRule);
    }

    public function testTravelFeeOfALongTrip(): void
    {
        $paris = new GeoPoint(48.8566, 2.3522);

        $price = $this->calculator()->calculate($this->travelPolicy, $this->guestRange, null, new \DateTimeImmutable('2027-06-12'), $paris);

        $expectedDistance = $this->travelPolicy->estimateRoadDistanceKm($paris);
        self::assertSame($expectedDistance, $price->distanceKm);
        self::assertSame(40 + $expectedDistance - 20, $price->travelPrice);
        self::assertSame(730 + $price->travelPrice, $price->total());
    }

    private function calculator(?SpecialDate $specialDate = null): QuoteCalculator
    {
        $repository = $this->createStub(SpecialDateRepository::class);
        $repository->method('findOneForDate')->willReturnCallback(
            static fn (\DateTimeInterface $date): ?SpecialDate => $specialDate?->matches($date) ? $specialDate : null,
        );

        return new QuoteCalculator($repository);
    }
}
