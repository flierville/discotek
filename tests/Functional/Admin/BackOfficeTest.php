<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Entity\ContactMessage;
use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Entity\Page;
use App\Entity\Settings;
use App\Enum\EventPricing;
use App\Enum\PageKind;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BackOfficeTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
    }

    #[DataProvider('screens')]
    public function testEveryScreenIsDisplayed(string $url, string $title): void
    {
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $title);
    }

    /** @return iterable<array{string, string}> */
    public static function screens(): iterable
    {
        yield ['/admin', 'Bonjour'];
        yield ['/admin/pages', 'Pages'];
        yield ['/admin/pages/nouvelle', 'Nouvelle page'];
        yield ['/admin/devis', 'Devis'];
        yield ['/admin/devis/regles', 'Règles de calcul des devis'];
        yield ['/admin/devis/regles/personnes/nouveau', 'Ajouter une tranche de personnes'];
        yield ['/admin/devis/regles/evenements/nouveau', "Ajouter une type d'événement"];
        yield ['/admin/devis/regles/dates/nouveau', 'Ajouter une date spéciale'];
        yield ['/admin/contacts', 'Demandes de contact'];
        yield ['/admin/soirees', 'Photos de soirées'];
        yield ['/admin/soirees/nouvelle', 'Nouvelle soirée'];
        yield ['/admin/newsletter', 'Abonnés à la newsletter'];
        yield ['/admin/parametres', 'Paramètres du site'];
        yield ['/admin/mon-compte', 'Mon compte'];
    }

    public function testPageIsEditedWithTheWysiwygEditor(): void
    {
        $page = $this->entityManager()->getRepository(Page::class)->findOneBy(['slug' => 'liens-partenaires']);
        $crawler = $this->client->request('GET', '/admin/pages/'.$page?->getId());
        self::assertSelectorExists('textarea[data-controller="wysiwyg"]');

        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'page_form[title]' => 'Nos partenaires',
            'page_form[content]' => '<h1>Partenaires</h1><p style="color: #ff00e1;">Texte <strong>gras</strong></p><script>alert(1)</script>',
            'page_form[metaDescription]' => 'Les partenaires du DJ',
        ]);

        self::assertResponseRedirects('/admin/pages/'.$page?->getId());
        $this->client->request('GET', '/liens-partenaires');
        self::assertSelectorTextContains('.rich-text h1', 'Partenaires');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('<strong>gras</strong>', $html);
        self::assertStringNotContainsString('alert(1)', $html, 'Scripts are removed by the HTML sanitizer.');
        self::assertStringContainsString('content="Les partenaires du DJ"', $html);
    }

    public function testNewPageGetsItsAddressFromTheTitle(): void
    {
        $crawler = $this->client->request('GET', '/admin/pages/nouvelle');
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'page_form[title]' => 'Nos Tarifs Été',
            'page_form[slug]' => '',
            'page_form[content]' => '<p>Bientôt</p>',
            'page_form[position]' => '60',
        ]);

        self::assertResponseRedirects();
        self::assertNotNull($this->entityManager()->getRepository(Page::class)->findOneBy(['slug' => 'nos-tarifs-ete']));
        $this->client->request('GET', '/nos-tarifs-ete');
        self::assertResponseIsSuccessful();
    }

    public function testFunctionalPagesAreUnique(): void
    {
        $crawler = $this->client->request('GET', '/admin/pages/nouvelle');
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'page_form[title]' => 'Autre contact',
            'page_form[kind]' => PageKind::Contact->value,
            'page_form[position]' => '1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-error', 'Une page de type "Contact (formulaire)" existe déjà');
    }

    public function testQuoteRuleIsChangedAndUsedByTheForm(): void
    {
        $range = $this->entityManager()->getRepository(GuestRange::class)->findOneBy(['label' => 'Plus de 180 personnes']);
        $crawler = $this->client->request('GET', '/admin/devis/regles/personnes/'.$range?->getId());
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'guest_range_form[price]' => '900',
            'guest_range_form[active]' => false,
        ]);

        self::assertResponseRedirects('/admin/devis/regles');
        $this->entityManager()->clear();
        $range = $this->entityManager()->getRepository(GuestRange::class)->findOneBy(['label' => 'Plus de 180 personnes']);
        self::assertSame(900, $range?->getPrice());
        self::assertFalse($range?->isActive());

        $crawler = $this->client->request('GET', '/devis-en-ligne');
        self::assertNotContains('Plus de 180 personnes', $crawler->filter('#quote_request_guestRange option')->each(static fn ($option): string => $option->text()));
    }

    public function testEventTypeGetsABonusOrMalus(): void
    {
        $wedding = $this->entityManager()->getRepository(EventType::class)->findOneBy(['label' => 'Mariage']);
        $crawler = $this->client->request('GET', '/admin/devis/regles/evenements/'.$wedding?->getId());
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'event_type_form[pricing]' => EventPricing::Adjustment->value,
            'event_type_form[priceAdjustment]' => '-50',
        ]);

        self::assertResponseRedirects('/admin/devis/regles');
        $this->entityManager()->clear();
        $wedding = $this->entityManager()->getRepository(EventType::class)->findOneBy(['label' => 'Mariage']);
        self::assertSame(-50, $wedding?->getPriceAdjustment());
        self::assertNull($wedding?->getFixedPrice());
    }

    public function testBonusOrMalusAmountIsRequired(): void
    {
        $crawler = $this->client->request('GET', '/admin/devis/regles/evenements/nouveau');
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'event_type_form[label]' => 'Gala',
            'event_type_form[pricing]' => EventPricing::Adjustment->value,
            'event_type_form[position]' => '110',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-error', 'Indiquez le montant du bonus / malus.');
    }

    public function testSpecialDateCannotBeDefinedTwice(): void
    {
        $crawler = $this->client->request('GET', '/admin/devis/regles/dates/nouveau');
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'special_date_form[label]' => 'Doublon',
            'special_date_form[day]' => '31',
            'special_date_form[month]' => '12',
            'special_date_form[price]' => '1200',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-error', 'Un tarif existe déjà pour cette date.');
    }

    public function testTravelFeesAreConfigurable(): void
    {
        $crawler = $this->client->request('GET', '/admin/devis/regles');
        $this->client->submit($crawler->selectButton('Enregistrer les frais de déplacement')->form(), [
            'travel_settings_form[shortTripMaxKm]' => '30',
            'travel_settings_form[longTripPricePerKm]' => '0.8',
        ]);

        self::assertResponseRedirects('/admin/devis/regles');
        $settings = $this->entityManager()->getRepository(Settings::class)->findOneBy([]);
        self::assertSame(30, $settings?->getShortTripMaxKm());
        self::assertSame(0.8, $settings?->getLongTripPricePerKm());
    }

    public function testContactRecipientsAreConfigurable(): void
    {
        $crawler = $this->client->request('GET', '/admin/parametres');
        $this->client->submit($crawler->selectButton('Enregistrer les paramètres')->form(), [
            'site_settings_form[contactRecipients]' => "patrick@example.com\nsophie@example.com",
        ]);
        self::assertResponseRedirects('/admin/parametres');
        self::assertSame(['patrick@example.com', 'sophie@example.com'], $this->entityManager()->getRepository(Settings::class)->findOneBy([])?->getContactRecipients());

        $crawler = $this->client->request('GET', '/admin/parametres');
        $this->client->submit($crawler->selectButton('Enregistrer les paramètres')->form(), [
            'site_settings_form[contactRecipients]' => 'pas-un-email',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testContactMessageIsMarkedAsReadThenDeleted(): void
    {
        $message = new ContactMessage('Paul', 'paul@example.com', null, 'Bonjour');
        $this->entityManager()->persist($message);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', '/admin/contacts/'.$message->getId());
        self::assertSelectorTextContains('h1', 'Message de Paul');
        self::assertTrue($message->isRead());

        $this->client->submit($crawler->filter('form[data-controller="confirm"]')->form());
        self::assertResponseRedirects('/admin/contacts');
        self::assertNull($this->entityManager()->getRepository(ContactMessage::class)->find((int) $message->getId()));
    }

    public function testNewsletterExport(): void
    {
        $this->client->request('GET', '/admin/newsletter/export.csv');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
    }
}
