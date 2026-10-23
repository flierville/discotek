<?php

declare(strict_types=1);

namespace App\Tests\E2E;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Browser tests in a real Chrome (Panther), against the test database loaded by tests/bootstrap.php.
 * The web server runs in another process: the data written here must be committed (no rollback).
 */
#[SkipDatabaseRollback]
abstract class E2ETestCase extends PantherTestCase
{
    protected function browser(int $width = 1300, int $height = 900): Client
    {
        $client = static::createPantherClient(['browser' => static::CHROME], [], [
            'capabilities' => ['goog:loggingPrefs' => ['browser' => 'ALL']],
        ]);
        $client->manage()->window()->setSize(new WebDriverDimension($width, $height));

        return $client;
    }
}
