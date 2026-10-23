<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminCommandTest extends KernelTestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->tester = new CommandTester((new Application(self::bootKernel()))->find('app:create-admin'));
    }

    /**
     * The interactive hidden prompt cannot be simulated on Windows (it reads the console directly).
     */
    public function testAdministratorIsCreated(): void
    {
        $exitCode = $this->tester->execute(['email' => 'Nouveau@Example.com', '--name' => 'Sophie', '--password' => 'Un-Mot-De-Passe-Solide']);

        self::assertSame(Command::SUCCESS, $exitCode);
        $user = $this->users()->findOneBy(['email' => 'nouveau@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('Sophie', $user->getName());
        self::assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'Un-Mot-De-Passe-Solide'));
    }

    public function testRunningItAgainResetsThePassword(): void
    {
        $this->tester->execute(['email' => 'reset@example.com', '--password' => 'Premier-Mot-De-Passe']);
        $exitCode = $this->tester->execute(['email' => 'reset@example.com', '--password' => 'Second-Mot-De-Passe']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Mot de passe de reset@example.com mis à jour', $this->tester->getDisplay());
        self::assertSame(1, $this->users()->count(['email' => 'reset@example.com']));
    }

    public function testWeakPasswordAndInvalidEmailAreRefused(): void
    {
        self::assertSame(Command::INVALID, $this->tester->execute(['email' => 'admin2@example.com', '--password' => 'court']));
        self::assertSame(Command::INVALID, $this->tester->execute(['email' => 'pas-un-email', '--password' => 'Un-Mot-De-Passe-Solide']));
    }

    /** @return EntityRepository<User> */
    private function users(): EntityRepository
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class);
    }
}
