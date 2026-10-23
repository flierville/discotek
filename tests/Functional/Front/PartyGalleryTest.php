<?php

declare(strict_types=1);

namespace App\Tests\Functional\Front;

use App\DataFixtures\AppFixtures;
use App\Entity\Party;
use App\Entity\PhotoAccessLog;
use App\Party\PhotoImporter;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Support\TestImages;

final class PartyGalleryTest extends FunctionalTestCase
{
    public function testGuestsOpenTheGalleryWithThePartyCode(): void
    {
        $photo = $this->addPhotoToTheParty();

        $this->requestAccess('  FIESTA ');

        self::assertResponseRedirects('/photos-de-soirees/'.AppFixtures::PARTY_SLUG);
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Mariage de Julie et David');
        self::assertCount(1, $crawler->filter('.gallery__item'));
        self::assertSame('noindex, nofollow', $crawler->filter('meta[name="robots"]')->attr('content'));

        $this->client->request('GET', \sprintf('/photos-de-soirees/%s/thumb/%s', AppFixtures::PARTY_SLUG, $photo));
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/jpeg');

        $log = $this->entityManager()->getRepository(PhotoAccessLog::class)->findOneBy(['email' => 'invite@example.com']);
        self::assertSame('Rouen', $log?->getCity());
    }

    public function testUnknownCodeIsRefused(): void
    {
        $this->requestAccess('mauvais-code');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-errors', 'Le code soirée est introuvable');
    }

    public function testCodeOfAHiddenPartyIsRefused(): void
    {
        $this->requestAccess(AppFixtures::HIDDEN_PARTY_CODE);

        self::assertResponseStatusCodeSame(422);
    }

    public function testGalleryAndPhotosAreProtected(): void
    {
        $photo = $this->addPhotoToTheParty();

        $this->client->request('GET', '/photos-de-soirees/'.AppFixtures::PARTY_SLUG);
        self::assertResponseRedirects('/photos-de-soirees');

        $this->client->request('GET', \sprintf('/photos-de-soirees/%s/large/%s', AppFixtures::PARTY_SLUG, $photo));
        self::assertResponseStatusCodeSame(404);
    }

    public function testLinksSentByTheOldSiteStillOpenTheGallery(): void
    {
        $this->client->request('GET', '/?nom=photos-de-soirees&page=photos&photoid=12&code='.AppFixtures::PARTY_CODE);

        self::assertResponseRedirects('/photos-de-soirees/'.AppFixtures::PARTY_SLUG, 301);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    private function requestAccess(string $code): void
    {
        $crawler = $this->client->request('GET', '/photos-de-soirees');
        $this->client->submit($crawler->filter('form#form_photo')->form(), [
            'photo_access[name]' => 'Invité',
            'photo_access[email]' => 'invite@example.com',
            'photo_access[location][name]' => 'Rouen',
            'photo_access[code]' => $code,
        ]);
    }

    private function addPhotoToTheParty(): string
    {
        $party = $this->entityManager()->getRepository(Party::class)->findOneBy(['slug' => AppFixtures::PARTY_SLUG]);
        \assert($party instanceof Party);
        $dir = TestImages::tempDir();
        $result = static::getContainer()->get(PhotoImporter::class)->import($party, TestImages::jpeg($dir.'/IMG_1.jpg'), 'IMG_1.jpg');

        return $result->added[0]->getFilename();
    }
}
