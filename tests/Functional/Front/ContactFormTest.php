<?php

declare(strict_types=1);

namespace App\Tests\Functional\Front;

use App\DataFixtures\AppFixtures;
use App\Entity\ContactMessage;
use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Mime\Email;

final class ContactFormTest extends FunctionalTestCase
{
    public function testMessageIsLoggedAndSentToEveryConfiguredRecipient(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $this->client->submit($crawler->filter('form#form_contact')->form(), [
            'contact[name]' => 'Paul Durand',
            'contact[email]' => 'Paul@Example.com',
            'contact[phone]' => '0612345678',
            'contact[message]' => "Bonjour,\nêtes-vous disponible le 12 juin ?",
        ]);

        self::assertResponseRedirects('/information');

        $message = $this->entityManager()->getRepository(ContactMessage::class)->findOneBy(['email' => 'paul@example.com']);
        self::assertInstanceOf(ContactMessage::class, $message);
        self::assertSame('Paul Durand', $message->getName());
        self::assertFalse($message->isRead());
        self::assertNotNull($message->getIpAddress());

        self::assertEmailCount(2);
        $adminEmail = self::getMailerMessage(0);
        \assert($adminEmail instanceof Email);
        foreach (AppFixtures::CONTACT_RECIPIENTS as $recipient) {
            self::assertEmailAddressContains($adminEmail, 'To', $recipient);
        }
        self::assertEmailAddressContains($adminEmail, 'Reply-To', 'paul@example.com');
        self::assertEmailHtmlBodyContains($adminEmail, 'êtes-vous disponible le 12 juin ?');

        $confirmation = self::getMailerMessage(1);
        \assert($confirmation instanceof Email);
        self::assertEmailAddressContains($confirmation, 'To', 'paul@example.com');
        self::assertEmailHeaderSame($confirmation, 'Subject', 'Confirmation de Message sur le site DiscoTek Animation');
    }

    public function testMessageIsRequired(): void
    {
        $crawler = $this->client->request('GET', '/contact');
        $this->client->submit($crawler->filter('form#form_contact')->form(), [
            'contact[name]' => 'Paul',
            'contact[email]' => 'paul@example.com',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Veuillez saisir votre message.');
        self::assertEmailCount(0);
    }
}
