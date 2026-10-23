<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\City;
use App\Entity\Party;
use App\Entity\Settings;
use App\Entity\SpecialDate;
use App\Entity\User;
use App\Enum\PageKind;
use PHPUnit\Framework\TestCase;

final class EntityRulesTest extends TestCase
{
    public function testPartyCodeIsCaseInsensitive(): void
    {
        $party = (new Party())->setAccessCode('  FiEsTa ');

        self::assertSame('fiesta', $party->getAccessCode());
        self::assertSame('fiesta', Party::normalizeCode('FIESTA'));
    }

    public function testSpecialDateMatchesEveryYear(): void
    {
        $newYearEve = new SpecialDate('Saint-Sylvestre', 31, 12, 1000);

        self::assertTrue($newYearEve->matches(new \DateTimeImmutable('2027-12-31')));
        self::assertTrue($newYearEve->matches(new \DateTimeImmutable('2031-12-31 23:00')));
        self::assertFalse($newYearEve->matches(new \DateTimeImmutable('2027-12-30')));
    }

    public function testRecipientsAreNormalized(): void
    {
        $settings = (new Settings())->setContactRecipients([' DJ@Example.com', '', 'dj@example.com', 'autre@example.com']);

        self::assertSame(['dj@example.com', 'autre@example.com'], $settings->getContactRecipients());
    }

    public function testCityNameIsSearchableWithoutAccentsNorDashes(): void
    {
        self::assertSame('saint etienne du rouvray', City::normalize('Saint-Étienne-du-Rouvray'));
        self::assertSame('evreux', City::normalize('ÉVREUX'));
    }

    public function testUserIsAlwaysAnAdministrator(): void
    {
        $user = new User(' Admin@Example.com ');

        self::assertSame('admin@example.com', $user->getUserIdentifier());
        self::assertContains(User::ROLE_ADMIN, $user->getRoles());
    }

    public function testOnlyStandardPagesCanExistSeveralTimes(): void
    {
        self::assertFalse(PageKind::Standard->isUnique());
        self::assertTrue(PageKind::Quote->isUnique());
        self::assertTrue(PageKind::Home->isUnique());
    }
}
