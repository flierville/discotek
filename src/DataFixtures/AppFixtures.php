<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\Page;
use App\Entity\Party;
use App\Entity\Settings;
use App\Entity\User;
use App\Enum\PageKind;
use App\Quote\DefaultQuoteRules;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Data set of the automated tests (and of a fresh development database).
 */
final class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@example.com';
    public const ADMIN_PASSWORD = 'Admin-Password-2026';
    public const PARTY_CODE = 'fiesta';
    public const PARTY_SLUG = 'mariage-julie-david';
    public const HIDDEN_PARTY_CODE = 'cachee';
    public const CONTACT_RECIPIENTS = ['dj@example.com', 'associe@example.com'];
    public const QUOTE_RECIPIENT = 'devis@example.com';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User(self::ADMIN_EMAIL))->setName('Patrick');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_PASSWORD));
        $manager->persist($admin);

        $manager->persist((new Settings())
            ->setMetaKeywords('dj, disc jockey, animation, mariage, normandie')
            ->setMetaDescription('Discotek-Animation, Disc Jockey en Seine Martime (76) en Haute Normandie')
            ->setContactRecipients(self::CONTACT_RECIPIENTS)
            ->setQuoteRecipients([self::QUOTE_RECIPIENT]));

        foreach ([...DefaultQuoteRules::guestRanges(), ...DefaultQuoteRules::eventTypes(), ...DefaultQuoteRules::specialDates()] as $rule) {
            $manager->persist($rule);
        }

        $this->loadPages($manager);
        $this->loadCities($manager);
        $this->loadParties($manager);

        $manager->flush();
    }

    private function loadPages(ObjectManager $manager): void
    {
        $pages = [
            ['Accueil', 'accueil', PageKind::Home, '<h1>Présentation</h1><p>Implantée en seine maritime (76), la société Disco-Tek Animation est spécialisée dans l\'animation.</p>', null, 1],
            ['Devis en ligne', 'devis-en-ligne', PageKind::Quote, '<h1>Tarifs en ligne</h1><p>En 2 minutes à peine, recevez immédiatement sur votre boite mail</p>', '#ff0000', 2],
            ['Contact', 'contact', PageKind::Contact, '<h1>Contactez moi</h1><p>Par téléphone au 06.64.95.10.09</p>', null, 3],
            ['Liens / Partenaires', 'liens-partenaires', PageKind::Standard, '<h1>Liens / Partenaires</h1><ul><li><a href="http://www.fabienlemagicien.com">Fabien le Magicien</a></li></ul>', null, 4],
            ['Photos de soirees', 'photos-de-soirees', PageKind::PartyPhotos, '<h1>Photos des soirées</h1><p>Cet espace étant protégé, veuillez renseigner le formulaire ci-dessous.</p>', null, 5],
        ];

        foreach ($pages as [$title, $slug, $kind, $content, $color, $legacyId]) {
            $manager->persist((new Page())
                ->setTitle($title)
                ->setSlug($slug)
                ->setKind($kind)
                ->setContent($content)
                ->setInMenu(true)
                ->setMenuColor($color)
                ->setPosition($legacyId * 10)
                ->setLegacyId($legacyId));
        }

        $manager->persist((new Page())
            ->setTitle('Mentions Légales')
            ->setSlug('mentions-legales')
            ->setContent('<h1>Mentions Légales</h1><p>SIRET : 514 458 637 00014</p>')
            ->setInFooter(true)
            ->setPosition(70)
            ->setLegacyId(7));

        $manager->persist((new Page())
            ->setTitle('Page brouillon')
            ->setSlug('page-brouillon')
            ->setContent('<p>Pas encore publiée</p>')
            ->setPublished(false)
            ->setPosition(80));
    }

    private function loadCities(ObjectManager $manager): void
    {
        $cities = [
            ['Rouen', '76000', 'Seine-Maritime', 'Haute-Normandie', 49.4431, 1.0993],
            ['Rouen', '76001', 'Seine-Maritime', 'Haute-Normandie', 49.4431, 1.0993], // CEDEX: merged in the autocomplete
            ['Grugny', '76690', 'Seine-Maritime', 'Haute-Normandie', 49.6167, 1.1],
            ['Saint-Étienne-du-Rouvray', '76800', 'Seine-Maritime', 'Haute-Normandie', 49.3779, 1.105],
            ['Évreux', '27000', 'Eure', 'Haute-Normandie', 49.0241, 1.1508],
            ['Paris', '75001', 'Paris', 'Île-de-France', 48.8592, 2.3417],
        ];

        foreach ($cities as [$name, $postalCode, $department, $region, $latitude, $longitude]) {
            $manager->persist(new City($name, $postalCode, $department, $region, $latitude, $longitude));
        }
    }

    private function loadParties(ObjectManager $manager): void
    {
        $manager->persist((new Party())
            ->setTitle('Mariage de Julie et David')
            ->setSlug(self::PARTY_SLUG)
            ->setAccessCode(self::PARTY_CODE)
            ->setEventDate(new \DateTimeImmutable('2026-06-13')));

        $manager->persist((new Party())
            ->setTitle('Soirée privée masquée')
            ->setSlug('soiree-masquee')
            ->setAccessCode(self::HIDDEN_PARTY_CODE)
            ->setPublished(false));
    }
}
