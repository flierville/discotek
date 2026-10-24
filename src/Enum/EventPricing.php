<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * How an event type changes the price of the guest range.
 */
enum EventPricing: string
{
    case GuestRange = 'guest_range';
    case Fixed = 'fixed';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::GuestRange => 'Prix selon le nombre de personnes',
            self::Fixed => 'Prix fixe',
            self::Adjustment => 'Bonus / malus sur le prix selon le nombre de personnes',
        };
    }
}
