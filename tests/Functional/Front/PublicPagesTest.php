<?php

declare(strict_types=1);

namespace App\Tests\Functional\Front;

use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PublicPagesTest extends FunctionalTestCase
{
    public function testHomePageKeepsTheLegacyTitleAndMenu(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame('Discotek-Animation - Disc Jockey Haute Normandie, Seine Maritime Rouen');
        self::assertSelectorTextSame('.site-header__title .text-disco-pink', 'Disco-Tek');
        self::assertSelectorTextSame('.site-header__title .text-disco-yellow', 'Animation');
        self::assertSelectorTextContains('.rich-text h1', 'Présentation');
        self::assertSame(
            ['Accueil', 'Devis en ligne', 'Contact', 'Liens / Partenaires', 'Photos de soirees', 'Les avis'],
            $crawler->filter('.site-menu__item a')->each(static fn ($link): string => $link->text()),
        );
        self::assertSame('color:#ff0000;--menu-color:#ff0000', $crawler->filter('.site-menu__item a')->eq(1)->attr('style'));
        self::assertSelectorTextContains('.site-footer a', 'Mentions Légales');
        self::assertSame('dj, disc jockey, animation, mariage, normandie', $crawler->filter('meta[name="keywords"]')->attr('content'));
    }

    #[DataProvider('pages')]
    public function testEveryPageIsReachableWithAReadableAddress(string $url, string $title, ?string $formId): void
    {
        $crawler = $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($title.' | Discotek-Animation - Disc Jockey Haute Normandie, Seine Maritime Rouen');
        if (null !== $formId) {
            self::assertCount(1, $crawler->filter('form#'.$formId));
        }
    }

    /** @return iterable<string, array{string, string, ?string}> */
    public static function pages(): iterable
    {
        yield 'quote' => ['/devis-en-ligne', 'Devis en ligne', 'form_devis'];
        yield 'contact' => ['/contact', 'Contact', 'form_contact'];
        yield 'links' => ['/liens-partenaires', 'Liens / Partenaires', null];
        yield 'party photos' => ['/photos-de-soirees', 'Photos de soirees', 'form_photo'];
        yield 'legal notice' => ['/mentions-legales', 'Mentions Légales', null];
    }

    #[DataProvider('legacyUrls')]
    public function testOldUrlsArePermanentlyRedirected(string $legacyUrl, string $newUrl): void
    {
        $this->client->request('GET', $legacyUrl);

        self::assertResponseStatusCodeSame(301);
        self::assertResponseRedirects($newUrl);
    }

    /** @return iterable<string, array{string, string}> */
    public static function legacyUrls(): iterable
    {
        yield 'quote page' => ['/?nom=devis-en-ligne&page=pages&pageid=2', '/devis-en-ligne'];
        yield 'contact page' => ['/?nom=contact&page=pages&pageid=3', '/contact'];
        yield 'legal notice' => ['/?nom=mentions-legales&page=pages&pageid=7', '/mentions-legales'];
        yield 'home page' => ['/?nom=accueil&page=pages&pageid=1', '/'];
        yield 'index.php' => ['/index.php', '/'];
        yield 'message page' => ['/?page=message&mess=1', '/'];
        yield 'unknown party code' => ['/?nom=photos-de-soirees&page=photos&photoid=12&code=inconnu', '/photos-de-soirees'];
    }

    public function testUnpublishedAndUnknownPagesAreNotFound(): void
    {
        $this->client->request('GET', '/page-brouillon');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/cette-page-n-existe-pas');
        self::assertResponseStatusCodeSame(404);
    }

    public function testHomePageSlugRedirectsToTheRoot(): void
    {
        $this->client->request('GET', '/accueil');

        self::assertResponseRedirects('/', 301);
    }

    public function testSitemapListsThePublishedPages(): void
    {
        $this->client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('<loc>http://localhost/devis-en-ligne</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/</loc>', $content);
        self::assertStringNotContainsString('page-brouillon', $content);
    }

    public function testRobotsProtectsTheBackOfficeAndGalleries(): void
    {
        $this->client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Disallow: /admin', $content);
        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $content);
    }

    public function testCityAutocomplete(): void
    {
        $this->client->request('GET', '/api/villes?q=saint-etienne');

        self::assertResponseIsSuccessful();
        $cities = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('Saint-Étienne-du-Rouvray', $cities[0]['name']);
        self::assertSame('76800', $cities[0]['postalCode']);

        $this->client->request('GET', '/api/villes?q=76');
        self::assertSame(['Rouen', 'Grugny', 'Saint-Étienne-du-Rouvray'], array_column(json_decode((string) $this->client->getResponse()->getContent(), true), 'name'));
    }
}
