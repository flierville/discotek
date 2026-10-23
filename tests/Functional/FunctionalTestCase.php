<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DataFixtures\AppFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The database contains the AppFixtures; each test is rolled back by DAMADoctrineTestBundle.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Same origin requests, like a browser (stateless CSRF protection of the forms).
        $this->client->setServerParameter('HTTP_ORIGIN', 'http://localhost');
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function loginAsAdmin(): User
    {
        $admin = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => AppFixtures::ADMIN_EMAIL]);
        \assert($admin instanceof User);
        $this->client->loginUser($admin);

        return $admin;
    }
}
