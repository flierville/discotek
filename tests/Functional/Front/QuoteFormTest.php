<?php

declare(strict_types=1);

namespace App\Tests\Functional\Front;

use App\DataFixtures\AppFixtures;
use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Entity\NewsletterSubscriber;
use App\Entity\QuoteRequest;
use App\Enum\EventPricing;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Mime\Email;

final class QuoteFormTest extends FunctionalTestCase
{
    public function testVisitorReceivesTheQuoteByEmail(): void
    {
        $this->submitQuote([
            'quote_request[eventType]' => (string) $this->eventTypeId('Mariage'),
            'quote_request[location][name]' => 'Rouen',
            'quote_request[location][postalCode]' => '76000',
            'quote_request[location][latitude]' => '49.4431',
            'quote_request[location][longitude]' => '1.0993',
            'quote_request[newsletter]' => '1',
        ]);

        self::assertResponseRedirects('/information');
        $quote = $this->entityManager()->getRepository(QuoteRequest::class)->findOneBy(['email' => 'julie@example.com']);
        self::assertInstanceOf(QuoteRequest::class, $quote);
        self::assertSame(730, $quote->getServicePrice());
        self::assertSame(23, $quote->getDistanceKm());
        self::assertSame(43, $quote->getTravelPrice());
        self::assertSame(773, $quote->getTotalPrice());
        self::assertSame('Mariage', $quote->getEventType());

        self::assertEmailCount(2);
        $adminEmail = self::getMailerMessage(0);
        \assert($adminEmail instanceof Email);
        self::assertEmailAddressContains($adminEmail, 'To', AppFixtures::QUOTE_RECIPIENT);
        self::assertEmailAddressContains($adminEmail, 'Reply-To', 'julie@example.com');
        self::assertEmailHeaderSame($adminEmail, 'Subject', 'Demande de Tarif');

        $customerEmail = self::getMailerMessage(1);
        \assert($customerEmail instanceof Email);
        self::assertEmailAddressContains($customerEmail, 'To', 'julie@example.com');
        self::assertEmailHeaderSame($customerEmail, 'Subject', 'Confirmation de demande de tarif sur le site DiscoTek Animation');
        self::assertEmailHtmlBodyContains($customerEmail, '773 €');
        self::assertEmailHtmlBodyContains($customerEmail, 'Rouen');

        self::assertNotNull($this->entityManager()->getRepository(NewsletterSubscriber::class)->findOneBy(['email' => 'julie@example.com']));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.rich-text', 'Votre demande de tarif en ligne vient d\'être envoyée');
    }

    public function testNewYearsEveHasItsFixedPrice(): void
    {
        $this->submitQuote([
            'quote_request[eventDate]' => (new \DateTimeImmutable('next year'))->format('Y').'-12-31',
            'quote_request[location][name]' => 'Grugny',
        ]);

        self::assertResponseRedirects('/information');
        $quote = $this->entityManager()->getRepository(QuoteRequest::class)->findOneBy(['email' => 'julie@example.com']);
        self::assertSame(1000, $quote?->getServicePrice());
        self::assertSame(20, $quote?->getTravelPrice());
    }

    public function testBonusOfTheEventTypeIsAddedToTheGuestRangePrice(): void
    {
        $wedding = $this->entityManager()->getRepository(EventType::class)->findOneBy(['label' => 'Mariage']);
        $wedding?->setPricing(EventPricing::Adjustment)->setPriceAdjustment(70);
        $this->entityManager()->flush();

        $this->submitQuote([
            'quote_request[eventType]' => (string) $this->eventTypeId('Mariage'),
            'quote_request[location][name]' => 'Grugny',
        ]);

        self::assertResponseRedirects('/information');
        $quote = $this->entityManager()->getRepository(QuoteRequest::class)->findOneBy(['email' => 'julie@example.com']);
        self::assertSame(800, $quote?->getServicePrice());
    }

    public function testCityTypedWithoutTheAutocompleteIsFoundInTheDatabase(): void
    {
        $this->submitQuote(['quote_request[location][name]' => 'evreux']);

        self::assertResponseRedirects('/information');
        $quote = $this->entityManager()->getRepository(QuoteRequest::class)->findOneBy(['email' => 'julie@example.com']);
        self::assertSame('Évreux', $quote?->getCity());
        self::assertSame('27000', $quote?->getPostalCode());
    }

    public function testErrorsAreListedAndNothingIsSent(): void
    {
        $this->submitQuote([
            'quote_request[lastName]' => '',
            'quote_request[email]' => 'pas-un-email',
            'quote_request[phone]' => '12',
            'quote_request[location][name]' => 'Ville inconnue',
            'quote_request[eventDate]' => '2020-01-01',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Erreur(s) détectée(s)');
        foreach (['Veuillez saisir votre nom.', 'E-Mail invalide.', 'Veuillez saisir au moins 10 chiffres.', 'Veuillez sélectionner une ville dans la liste proposée.', 'La date doit être dans le futur.'] as $error) {
            self::assertSelectorTextContains('.form-errors', $error);
        }
        self::assertEmailCount(0);
        self::assertSame(0, $this->entityManager()->getRepository(QuoteRequest::class)->count([]));
    }

    public function testSpamBotsFillingTheHoneypotAreRejected(): void
    {
        $this->submitQuote(['quote_request[website]' => 'http://spam.example.com']);

        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    public function testOnlyActiveRulesAreProposed(): void
    {
        $crawler = $this->client->request('GET', '/devis-en-ligne');

        $eventTypes = $crawler->filter('#quote_request_eventType option')->each(static fn ($option): string => $option->text());
        self::assertContains("Réveillon du jour de l'an", $eventTypes);
        self::assertNotContains('Réveillon de Noël', $eventTypes);
        self::assertCount(6, $crawler->filter('#quote_request_guestRange option'));
    }

    /** @param array<string, string> $values */
    private function submitQuote(array $values): void
    {
        $crawler = $this->client->request('GET', '/devis-en-ligne');
        $form = $crawler->filter('form#form_devis')->form();

        $this->client->submit($form, array_merge([
            'quote_request[lastName]' => 'Martin',
            'quote_request[firstName]' => 'Julie',
            'quote_request[email]' => 'julie@example.com',
            'quote_request[phone]' => '06 12 34 56 78',
            'quote_request[guestRange]' => (string) $this->guestRangeId('Entre 80 et 135 personnes'),
            'quote_request[eventDate]' => (new \DateTimeImmutable('+3 months'))->format('Y-m-d'),
            'quote_request[details]' => 'Mariage dans une salle des fêtes',
        ], $values));
    }

    private function guestRangeId(string $label): int
    {
        return (int) $this->entityManager()->getRepository(GuestRange::class)->findOneBy(['label' => $label])?->getId();
    }

    private function eventTypeId(string $label): int
    {
        return (int) $this->entityManager()->getRepository(EventType::class)->findOneBy(['label' => $label])?->getId();
    }
}
