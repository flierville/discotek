<?php

declare(strict_types=1);

namespace App\Quote;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Entity\SpecialDate;

/**
 * Pricing rules hard coded in the legacy pages/script_devis.php, now editable in the back office.
 */
final class DefaultQuoteRules
{
    /** @return list<GuestRange> */
    public static function guestRanges(): array
    {
        return [
            new GuestRange('Moins de 45 personnes', 630, 10),
            new GuestRange('Entre 45 et 80 personnes', 630, 20),
            new GuestRange('Entre 80 et 135 personnes', 730, 30),
            new GuestRange('Entre 135 et 180 personnes', 730, 40),
            new GuestRange('Plus de 180 personnes', 830, 50),
        ];
    }

    /** @return list<EventType> */
    public static function eventTypes(): array
    {
        $christmas = new EventType('Réveillon de Noël', 1000, 55);
        // The legacy rule existed but the choice was not proposed in the form.
        $christmas->setActive(false);

        return [
            new EventType('Mariage', null, 10),
            new EventType('Anniversaire', null, 20),
            new EventType('Soirée à thèmes', null, 30),
            new EventType('Baptême', null, 40),
            new EventType("Réveillon du jour de l'an", 1000, 50),
            $christmas,
            new EventType('Comité des fêtes', null, 60),
            new EventType('Soirée municipale', null, 70),
            new EventType('Communion', null, 80),
            new EventType('Camping', null, 90),
            new EventType('Autre...', null, 100),
        ];
    }

    /** @return list<SpecialDate> */
    public static function specialDates(): array
    {
        return [
            new SpecialDate('Réveillon de Noël', 24, 12, 1000),
            new SpecialDate('Réveillon de la Saint-Sylvestre', 31, 12, 1000),
        ];
    }
}
