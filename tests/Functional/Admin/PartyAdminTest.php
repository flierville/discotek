<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\DataFixtures\AppFixtures;
use App\Entity\Party;
use App\Party\PartyStorage;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Support\TestImages;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PartyAdminTest extends FunctionalTestCase
{
    private PartyStorage $storage;
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsAdmin();
        $this->storage = static::getContainer()->get(PartyStorage::class);
        $this->workDir = TestImages::tempDir();
    }

    public function testPartyIsCreatedWithItsDirectory(): void
    {
        $crawler = $this->client->request('GET', '/admin/soirees/nouvelle');
        self::assertMatchesRegularExpression('/^[a-z2-9]{6}$/', (string) $crawler->filter('#party_form_accessCode')->attr('value'));

        $this->client->submit($crawler->selectButton('Créer la soirée')->form(), [
            'party_form[title]' => 'Anniversaire de Léa (18 ans)',
            'party_form[slug]' => '',
            'party_form[accessCode]' => 'LEA18',
            'party_form[eventDate]' => '2026-11-14',
        ]);

        $party = $this->entityManager()->getRepository(Party::class)->findOneBy(['accessCode' => 'lea18']);
        self::assertInstanceOf(Party::class, $party);
        self::assertSame('anniversaire-de-lea-18-ans', $party->getSlug());
        self::assertResponseRedirects('/admin/soirees/'.$party->getId());
        self::assertDirectoryExists($this->storage->directory($party->getSlug()));
    }

    public function testCodeMustBeUnique(): void
    {
        $crawler = $this->client->request('GET', '/admin/soirees/nouvelle');
        $this->client->submit($crawler->selectButton('Créer la soirée')->form(), [
            'party_form[title]' => 'Doublon',
            'party_form[accessCode]' => strtoupper(AppFixtures::PARTY_CODE),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.form-error', 'Ce code est déjà utilisé par une autre soirée.');
    }

    public function testPicturesAndZipArchivesAreUploaded(): void
    {
        $party = $this->party();
        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());

        $this->upload($crawler, $party, TestImages::jpeg($this->workDir.'/DSC_0001.jpg'), 'DSC_0001.jpg');
        self::assertResponseIsSuccessful();
        $json = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('done', $json['status']);
        self::assertCount(1, $json['photos']);

        $this->upload($crawler, $party, TestImages::zip($this->workDir.'/photos.zip', $this->workDir), 'photos.zip');
        $json = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertCount(2, $json['photos'], 'The 2 pictures of the archive are imported, the other entries ignored.');

        $this->entityManager()->clear();
        $party = $this->party();
        self::assertCount(3, $party->getPhotos());
        foreach ($party->getPhotos() as $photo) {
            self::assertFileExists($this->storage->photoPath($photo, PartyStorage::SIZE_LARGE));
            self::assertFileExists($this->storage->photoPath($photo, PartyStorage::SIZE_THUMB));
        }
        self::assertFileDoesNotExist(\dirname($this->storage->directory($party->getSlug())).'/evil.jpg');
    }

    public function testBigFilesAreSentInChunks(): void
    {
        $party = $this->party();
        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());
        $content = (string) file_get_contents(TestImages::jpeg($this->workDir.'/big.jpg'));
        $chunks = str_split($content, (int) ceil(\strlen($content) / 3));

        foreach ($chunks as $index => $chunk) {
            file_put_contents($this->workDir.'/chunk', $chunk);
            $this->upload($crawler, $party, $this->workDir.'/chunk', 'big.jpg', 'chunked-upload-1', $index, \count($chunks));
            self::assertResponseIsSuccessful();
        }

        $json = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('done', $json['status']);
        self::assertSame('big.jpg', $json['photos'][0]['name']);
    }

    public function testUploadWithoutTokenIsRefused(): void
    {
        $party = $this->party();
        $this->client->request('POST', '/admin/soirees/'.$party->getId().'/photos', ['name' => 'a.jpg'], [], ['HTTP_X_CSRF_TOKEN' => 'invalid']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeletingThePartyDeletesItsPhotos(): void
    {
        $party = $this->party();
        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());
        $this->upload($crawler, $party, TestImages::jpeg($this->workDir.'/a.jpg'), 'a.jpg');
        $directory = $this->storage->directory($party->getSlug());
        self::assertDirectoryExists($directory);

        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());
        $this->client->submit($crawler->filter('form[action$="/supprimer"][data-controller="confirm"]')->form());

        self::assertResponseRedirects('/admin/soirees');
        self::assertDirectoryDoesNotExist($directory);
        self::assertNull($this->entityManager()->getRepository(Party::class)->findOneBy(['slug' => AppFixtures::PARTY_SLUG]));
    }

    public function testRenamingTheSlugMovesTheDirectory(): void
    {
        $party = $this->party();
        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());
        $this->upload($crawler, $party, TestImages::jpeg($this->workDir.'/a.jpg'), 'a.jpg');

        $crawler = $this->client->request('GET', '/admin/soirees/'.$party->getId());
        $this->client->submit($crawler->selectButton('Enregistrer')->form(), ['party_form[slug]' => 'julie-et-david']);

        self::assertResponseRedirects();
        self::assertDirectoryExists($this->storage->directory('julie-et-david'));
        self::assertDirectoryDoesNotExist($this->storage->directory(AppFixtures::PARTY_SLUG));
        $this->storage->removeDirectory('julie-et-david');
    }

    private function upload(Crawler $page, Party $party, string $path, string $name, string $uploadId = 'upload-test-1', int $index = 0, int $count = 1): void
    {
        $token = (string) $page->filter('[data-controller="photo-uploader"]')->attr('data-photo-uploader-token-value');
        $this->client->request(
            'POST',
            '/admin/soirees/'.$party->getId().'/photos',
            ['name' => $name, 'uploadId' => $uploadId.'-'.md5($path.$name), 'index' => $index, 'count' => $count],
            ['chunk' => new UploadedFile($path, $name, test: true)],
            ['HTTP_X_CSRF_TOKEN' => $token, 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    private function party(): Party
    {
        $party = $this->entityManager()->getRepository(Party::class)->findOneBy(['slug' => AppFixtures::PARTY_SLUG]);
        \assert($party instanceof Party);

        return $party;
    }

    protected function tearDown(): void
    {
        $this->storage->removeDirectory(AppFixtures::PARTY_SLUG);
        parent::tearDown();
    }
}
