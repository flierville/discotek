<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\DataFixtures\AppFixtures;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityTest extends FunctionalTestCase
{
    #[DataProvider('protectedUrls')]
    public function testBackOfficeRequiresAnAdministrator(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseRedirects('/admin/login');
    }

    /** @return iterable<array{string}> */
    public static function protectedUrls(): iterable
    {
        yield ['/admin'];
        yield ['/admin/pages'];
        yield ['/admin/devis'];
        yield ['/admin/soirees'];
        yield ['/admin/parametres'];
        yield ['/admin/newsletter/export.csv'];
    }

    public function testAdministratorLogsIn(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href*="register"], a[href*="reset"]');

        $this->client->submit($crawler->selectButton('Se connecter')->form(), [
            'email' => AppFixtures::ADMIN_EMAIL,
            'password' => AppFixtures::ADMIN_PASSWORD,
        ]);

        self::assertResponseRedirects('/admin');
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Bonjour Patrick');
    }

    public function testWrongPasswordIsRefused(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->client->submit($crawler->selectButton('Se connecter')->form(), [
            'email' => AppFixtures::ADMIN_EMAIL,
            'password' => 'mauvais',
        ]);

        self::assertResponseRedirects('/admin/login');
        $this->client->followRedirect();
        self::assertSelectorExists('[role="alert"]');
    }
}
