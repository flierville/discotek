<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\DataFixtures\AppFixtures;
use App\Entity\Party;
use App\Entity\QuoteRequest;
use App\Party\PhotoImporter;
use App\Tests\Support\TestImages;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

final class PublicSiteTest extends E2ETestCase
{
    public function testVisitorAsksForAQuoteWithTheCityAutocomplete(): void
    {
        $client = $this->browser();
        $crawler = $client->request('GET', '/devis-en-ligne');

        $form = $crawler->filter('form#form_devis')->form([
            'quote_request[lastName]' => 'Durand',
            'quote_request[email]' => 'e2e-devis@example.com',
            'quote_request[phone]' => '0612345678',
        ]);
        $form['quote_request[guestRange]']->select($crawler->filter('#quote_request_guestRange option')->eq(1)->attr('value'));
        $client->executeScript(\sprintf(
            "document.getElementById('quote_request_eventDate').value = '%s';",
            (new \DateTimeImmutable('+2 months'))->format('Y-m-d'),
        ));

        // Autocomplete: type the beginning of the city then choose it in the list.
        $client->getWebDriver()->findElement(WebDriverBy::id('quote_request_location_name'))->sendKeys('Saint-Eti');
        $client->waitForVisibility('.autocomplete__option');
        $client->getCrawler()->filter('.autocomplete__option')->first()->click();
        self::assertSame('76800', $client->getCrawler()->filter('#quote_request_location_postalCode')->attr('value'));

        $client->submit($form);
        $client->waitFor('.rich-text h2');

        self::assertSelectorTextContains('.rich-text', "Votre demande de tarif en ligne vient d'être envoyée");
        $quote = static::getContainer()->get(EntityManagerInterface::class)->getRepository(QuoteRequest::class)->findOneBy(['email' => 'e2e-devis@example.com']);
        self::assertSame('Saint-Étienne-du-Rouvray', $quote?->getCity());
    }

    public function testGuestBrowsesThePhotosInTheLightbox(): void
    {
        $this->givenThePartyHasPhotos(3);
        $client = $this->browser();
        $crawler = $client->request('GET', '/photos-de-soirees');

        $client->submit($crawler->filter('form#form_photo')->form([
            'photo_access[name]' => 'Invité E2E',
            'photo_access[email]' => 'e2e-photos@example.com',
            'photo_access[location][name]' => 'Rouen',
            'photo_access[code]' => 'Fiesta',
        ]));
        $client->waitFor('.gallery');
        self::assertSelectorTextContains('h1', 'Mariage de Julie et David');
        self::assertCount(3, $client->getCrawler()->filter('.gallery__item'));

        $client->getCrawler()->filter('.gallery__item')->first()->click();
        $client->waitForVisibility('.lightbox');
        self::assertSelectorTextSame('.lightbox__counter', '1 / 3');

        $client->getCrawler()->filter('.lightbox__button--next')->click();
        self::assertSelectorTextSame('.lightbox__counter', '2 / 3');

        $client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_LEFT);
        $client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_LEFT);
        self::assertSelectorTextSame('.lightbox__counter', '3 / 3', 'The navigation loops.');

        $client->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);
        $client->waitForInvisibility('.lightbox');
    }

    public function testMenuFoldsOnSmartphones(): void
    {
        $client = $this->browser(390, 844);
        $client->request('GET', '/');

        self::assertSelectorIsNotVisible('.site-menu__list');
        $client->getCrawler()->filter('.site-menu__toggle')->click();
        $client->waitForVisibility('.site-menu__list');
        self::assertSelectorIsVisible('.site-menu__item a[href="/contact"]');

        $overflow = $client->executeScript('return document.documentElement.scrollWidth - window.innerWidth;');
        self::assertLessThanOrEqual(0, $overflow, 'No horizontal scroll on a phone.');
    }

    private function givenThePartyHasPhotos(int $count): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $party = $entityManager->getRepository(Party::class)->findOneBy(['slug' => AppFixtures::PARTY_SLUG]);
        \assert($party instanceof Party);
        if ($party->getPhotos()->count() >= $count) {
            return;
        }

        $dir = TestImages::tempDir();
        for ($i = $party->getPhotos()->count() + 1; $i <= $count; ++$i) {
            static::getContainer()->get(PhotoImporter::class)->import($party, TestImages::jpeg(\sprintf('%s/IMG_%d.jpg', $dir, $i), 1200, 800), \sprintf('IMG_%d.jpg', $i));
        }
    }
}
