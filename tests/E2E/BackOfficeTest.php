<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use App\DataFixtures\AppFixtures;
use App\Tests\Support\TestImages;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Panther\Client;

final class BackOfficeTest extends E2ETestCase
{
    public function testDjCreatesAPartyAndUploadsPhotosAndAZip(): void
    {
        $client = $this->loggedInBrowser();

        $crawler = $client->request('GET', '/admin/soirees/nouvelle');
        $client->getWebDriver()->findElement(WebDriverBy::id('party_form_title'))->sendKeys('Soirée E2E '.uniqid());
        self::assertMatchesRegularExpression('/^soiree-e2e-/', (string) $client->getCrawler()->filter('#party_form_slug')->attr('value'), 'The slug follows the title.');
        $client->submit($crawler->selectButton('Créer la soirée')->form(['party_form[accessCode]' => 'e2e'.random_int(1000, 9999)]));
        $client->waitFor('[data-controller="photo-uploader"]');

        // Several pictures and a zip in the same selection: they are queued and uploaded.
        $dir = TestImages::tempDir();
        $files = [
            TestImages::jpeg($dir.'/photo-1.jpg'),
            TestImages::jpeg($dir.'/photo-2.jpg', 1600, 2400),
            TestImages::zip($dir.'/archive.zip', $dir),
        ];
        $client->getWebDriver()->findElement(WebDriverBy::cssSelector('input[type="file"]'))->sendKeys(implode("\n", $files));

        $client->waitForElementToContain('[data-photo-uploader-target="progressLabel"]', 'Terminé', 60);
        self::assertSelectorTextContains('[data-photo-uploader-target="progressLabel"]', '4 photo(s) ajoutée(s)');
        self::assertCount(4, $client->getCrawler()->filter('[data-photo-uploader-target="grid"] figure'));

        // Deleting the party: confirmation dialog, then the list.
        $client->getCrawler()->filter('form[action$="/supprimer"][data-controller="confirm"] button')->click();
        $client->waitForVisibility('#confirm-dialog');
        $client->getCrawler()->filter('#confirm-dialog [data-confirm-accept]')->click();
        $client->wait()->until(WebDriverExpectedCondition::urlMatches('#/admin/soirees$#'));
        $client->waitFor('[role="status"]');
        self::assertSelectorTextContains('main', 'et ses 4 photos ont été supprimées');
    }

    public function testPageIsEditedInTheWysiwygEditor(): void
    {
        $client = $this->loggedInBrowser();
        $client->request('GET', '/admin/pages');
        $client->clickLink('Liens / Partenaires');

        $client->wait(30)->until(static fn ($driver): bool => true === $driver->executeScript('return window.tinymce?.activeEditor?.initialized === true;'));
        $client->executeScript("tinymce.activeEditor.setContent('<h1>Partenaires E2E</h1><p>Nouveau texte</p>');");
        // The legacy styles (yellow titles) are applied inside the editor too.
        $client->wait(15)->until(static fn ($driver): bool => 'rgb(255, 240, 0)' === $driver->executeScript(
            "return getComputedStyle(tinymce.activeEditor.getDoc().querySelector('h1')).color;",
        ));

        $client->executeScript('window.beforeSubmit = true;');
        $client->getCrawler()->filter('main form button[type="submit"].btn-primary')->click();
        $client->wait(15)->until(static fn ($driver): bool => true !== $driver->executeScript('return window.beforeSubmit === true;'));
        $client->waitFor('[role="status"]');
        self::assertSelectorTextContains('[role="status"]', 'La page a été enregistrée.');

        $client->request('GET', '/liens-partenaires');
        self::assertSelectorTextContains('.rich-text h1', 'Partenaires E2E');
    }

    private function loggedInBrowser(): Client
    {
        $client = $this->browser();
        $crawler = $client->request('GET', '/admin/login');
        if (!str_ends_with($client->getCurrentURL(), '/admin/login')) {
            return $client; // Same browser as the previous test: already logged in.
        }
        $client->submit($crawler->selectButton('Se connecter')->form([
            'email' => AppFixtures::ADMIN_EMAIL,
            'password' => AppFixtures::ADMIN_PASSWORD,
        ]));
        $client->waitFor('.page-title');

        return $client;
    }
}
