<?php

declare(strict_types=1);

namespace App\Tests\Unit\Geo;

use App\Geo\GeoPoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeoPointTest extends TestCase
{
    public function testDistanceBetweenRouenAndParis(): void
    {
        $rouen = new GeoPoint(49.4431, 1.0993);
        $paris = new GeoPoint(48.8566, 2.3522);

        self::assertEqualsWithDelta(111.7, $rouen->distanceTo($paris), 1.0);
        self::assertEqualsWithDelta($rouen->distanceTo($paris), $paris->distanceTo($rouen), 0.0001);
    }

    public function testDistanceToItselfIsZero(): void
    {
        $point = new GeoPoint(49.615395, 1.103135);

        self::assertSame(0.0, $point->distanceTo($point));
    }

    #[DataProvider('invalidCoordinates')]
    public function testInvalidCoordinatesAreRejected(float $latitude, float $longitude): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new GeoPoint($latitude, $longitude);
    }

    /** @return iterable<string, array{float, float}> */
    public static function invalidCoordinates(): iterable
    {
        yield 'latitude too high' => [90.1, 0];
        yield 'latitude too low' => [-91, 0];
        yield 'longitude too high' => [0, 180.5];
        yield 'longitude too low' => [0, -181];
    }
}
