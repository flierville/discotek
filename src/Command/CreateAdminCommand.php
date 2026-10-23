<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The only way to create a back office account (no registration form, no "forgot password").
 * Running it again for an existing email resets the password.
 */
#[AsCommand(name: 'app:create-admin', description: 'Crée un administrateur du back office (ou réinitialise son mot de passe)')]
final readonly class CreateAdminCommand
{
    private const MIN_PASSWORD_LENGTH = 10;

    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Adresse email (identifiant de connexion)')] string $email,
        #[Option(description: 'Nom affiché dans le back office')] ?string $name = null,
        #[Option(description: 'Mot de passe (sinon il est demandé de façon masquée)')] ?string $password = null,
    ): int {
        $email = mb_strtolower(trim($email));
        $emailViolations = $this->validator->validate($email, [new NotBlank(), new Email()]);
        if (\count($emailViolations) > 0) {
            $io->error(\sprintf('Adresse email invalide : %s', $email));

            return Command::INVALID;
        }

        $password ??= $io->askQuestion((new Question('Mot de passe'))->setHidden(true));
        $passwordViolations = $this->validator->validate((string) $password, [new NotBlank(), new Length(min: self::MIN_PASSWORD_LENGTH)]);
        if (\count($passwordViolations) > 0) {
            $io->error(\sprintf('Le mot de passe doit contenir au moins %d caractères.', self::MIN_PASSWORD_LENGTH));

            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $isNew = null === $user;
        $user ??= new User($email);
        if (null !== $name) {
            $user->setName($name);
        }
        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success($isNew
            ? \sprintf('Administrateur %s créé, il peut se connecter sur /admin.', $email)
            : \sprintf('Mot de passe de %s mis à jour.', $email));

        return Command::SUCCESS;
    }
}
