<?php

declare(strict_types=1);

namespace App\Tests\Unit\Quote;

use App\Entity\Settings;
use App\Geo\GeoPoint;
use App\Quote\TravelPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TravelPolicyTest extends TestCase
{
    /**
     * Legacy rule: <= 20 km => 20 EUR, otherwise 40 EUR + 1 EUR per km above 20 km.
     */
    #[DataProvider('legacyFees')]
    public function testLegacyTravelFees(int $distanceKm, int $expectedFee): void
    {
        self::assertSame($expectedFee, TravelPolicy::fromSettings(new Settings())->priceFor($distanceKm));
    }

    /** @return iterable<string, array{int, int}> */
    public static function legacyFees(): iterable
    {
        yield 'at the door' => [0, 20];
        yield 'short trip limit' => [20, 20];
        yield 'just above the limit' => [21, 41];
        yield 'Rouen area' => [45, 65];
        yield 'Paris' => [170, 190];
    }

    public function testFeesFollowTheConfiguredRules(): void
    {
        $policy = new TravelPolicy(new GeoPoint(49, 1), 1.0, 30, 0, 50, 0.5);

        self::assertSame(0, $policy->priceFor(30));
        self::assertSame(55, $policy->priceFor(40));
    }

    public function testRoadDistanceIsTheBirdFlightDistanceTimesTheRoadFactor(): void
    {
        $departure = new GeoPoint(49.615395, 1.103135);
        $rouen = new GeoPoint(49.4431, 1.0993);
        $policy = new TravelPolicy($departure, 1.22, 20, 20, 40, 1.0);

        $expected = (int) round($departure->distanceTo($rouen) * 1.22);

        self::assertSame($expected, $policy->estimateRoadDistanceKm($rouen));
        self::assertSame(23, $expected);
    }

    public function testDefaultDepartureIsGrugny(): void
    {
        $policy = TravelPolicy::fromSettings(new Settings());

        self::assertSame(0, $policy->estimateRoadDistanceKm(new GeoPoint(49.615395, 1.103135)));
        self::assertSame(1.22, $policy->roadFactor);
    }
}
