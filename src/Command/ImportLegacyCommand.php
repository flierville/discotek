<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\City;
use App\Entity\Page;
use App\Entity\Party;
use App\Entity\Settings;
use App\Enum\PageKind;
use App\Legacy\LegacyContentCleaner;
use App\Newsletter\NewsletterSubscription;
use App\Party\PartyStorage;
use App\Party\PhotoImporter;
use App\Quote\DefaultQuoteRules;
use App\Repository\CityRepository;
use App\Repository\EventTypeRepository;
use App\Repository\GuestRangeRepository;
use App\Repository\PageRepository;
use App\Repository\PartyRepository;
use App\Repository\SpecialDateRepository;
use App\Settings\SettingsProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Imports the data of the legacy PHP 5 site: settings, pages, cities, newsletter subscribers,
 * parties and their photos. Every step is idempotent: the command can be run again.
 */
#[AsCommand(name: 'app:import-legacy', description: "Importe les données de l'ancien site (base discotek-ancien et photos)")]
final readonly class ImportLegacyCommand
{
    private const PHOTO_CATEGORY = ';2;';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsProvider $settingsProvider,
        private PageRepository $pages,
        private PartyRepository $parties,
        private CityRepository $cities,
        private GuestRangeRepository $guestRanges,
        private EventTypeRepository $eventTypes,
        private SpecialDateRepository $specialDates,
        private NewsletterSubscription $newsletter,
        private PhotoImporter $photoImporter,
        private PartyStorage $partyStorage,
        private LegacyContentCleaner $cleaner,
        private ValidatorInterface $validator,
        #[Autowire('%env(LEGACY_DATABASE_URL)%')]
        private string $legacyDatabaseUrl,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Répertoire photos_soirees de l\'ancien site (photos non importées si absent)')] ?string $photosDir = null,
    ): int {
        $legacy = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($this->legacyDatabaseUrl));

        $io->title("Import de l'ancien site");
        $this->importSettings($legacy, $io);
        $this->importQuoteRules($io);
        $this->importPages($legacy, $io);
        $this->importCities($legacy, $io);
        $this->importSubscribers($legacy, $io);
        $this->importParties($legacy, $io);

        if (null !== $photosDir) {
            $this->importPhotos($photosDir, $io);
        }

        $io->success('Import terminé.');

        return Command::SUCCESS;
    }

    private function importSettings(Connection $legacy, SymfonyStyle $io): void
    {
        $settings = $this->settingsProvider->get();
        if (null !== $settings->getId()) {
            $io->writeln('Paramètres : déjà présents.');

            return;
        }

        $row = $legacy->fetchAssociative('SELECT * FROM discotek_parametres ORDER BY parametres_id LIMIT 1') ?: [];
        $siteEmail = $row['parametres_mail_site'] ?? $settings->getPublicEmail();

        $settings
            ->setSiteTitle($this->cleaner->cleanTitle($row['parametres_titre_site'] ?? $settings->getSiteTitle()))
            ->setMetaKeywords($row['parametres_mots_cles'] ?? null)
            ->setMetaDescription($this->cleaner->fixDoubleEncoding($row['parametres_description'] ?? ''))
            ->setPublicEmail($siteEmail)
            ->setContactRecipients([$siteEmail])
            ->setQuoteRecipients([$siteEmail]);

        $this->settingsProvider->save($settings);
        $io->writeln('Paramètres : importés.');
    }

    private function importQuoteRules(SymfonyStyle $io): void
    {
        $imported = 0;
        foreach ([
            [$this->guestRanges, DefaultQuoteRules::guestRanges(...)],
            [$this->eventTypes, DefaultQuoteRules::eventTypes(...)],
            [$this->specialDates, DefaultQuoteRules::specialDates(...)],
        ] as [$repository, $defaults]) {
            if (0 === $repository->count([])) {
                foreach ($defaults() as $rule) {
                    $this->entityManager->persist($rule);
                    ++$imported;
                }
            }
        }
        $this->entityManager->flush();
        $io->writeln(\sprintf('Règles de devis : %d créées.', $imported));
    }

    private function importPages(Connection $legacy, SymfonyStyle $io): void
    {
        $rows = $legacy->fetchAllAssociative(
            'SELECT * FROM discotek_contenu WHERE contenu_cat_id LIKE :category ORDER BY contenu_id',
            ['category' => '%;1;%'],
        );
        $slugger = new AsciiSlugger('fr');
        $imported = 0;

        foreach ($rows as $position => $row) {
            $internalId = (string) $row['contenu_id_interne'];
            // Party galleries were also stored in category 1 by mistake (e.g. "Anniv' d'Alain et Maddy").
            if (str_contains((string) $row['contenu_cat_id'], self::PHOTO_CATEGORY) || null !== $this->pages->findOneBy(['legacyId' => (int) $row['contenu_id']])) {
                continue;
            }

            $title = $this->cleaner->cleanTitle((string) $row['contenu_titre']);
            $kind = $this->pageKind($internalId);
            $page = (new Page())
                ->setTitle($title)
                ->setSlug($slugger->slug($title)->lower()->toString())
                ->setKind($kind)
                ->setContent($this->pageContent($kind, (string) $row['contenu_descriptif']))
                ->setMetaTitle($this->cleaner->cleanTitle((string) $row['contenu_titre_navigateur_web']) ?: null)
                ->setMetaDescription(trim((string) $row['contenu_resume']) ?: null)
                ->setMetaKeywords(trim((string) $row['contenu_mots_cles']) ?: null)
                ->setPublished('1' === (string) $row['contenu_choix_activation'])
                ->setInMenu(str_contains($internalId, 'menu'))
                ->setInFooter(str_contains($internalId, 'mentions_legales'))
                ->setMenuColor(preg_match('/color=(#[0-9a-fA-F]{6})/', $internalId, $color) ? mb_strtolower($color[1]) : null)
                ->setPosition(($position + 1) * 10)
                ->setLegacyId((int) $row['contenu_id']);

            $this->entityManager->persist($page);
            ++$imported;
        }

        $this->entityManager->flush();
        $io->writeln(\sprintf('Pages : %d importées.', $imported));
    }

    private function pageKind(string $internalId): PageKind
    {
        return match (true) {
            str_contains($internalId, 'accueil') => PageKind::Home,
            str_contains($internalId, 'tarif') => PageKind::Quote,
            str_contains($internalId, 'photos') => PageKind::PartyPhotos,
            str_contains($internalId, 'contact') => PageKind::Contact,
            default => PageKind::Standard,
        };
    }

    private function pageContent(PageKind $kind, string $html): string
    {
        $html = $this->cleaner->cleanPageContent($html);

        // The gallery is now opened directly after the form (no more link sent by email).
        if (PageKind::PartyPhotos === $kind) {
            $html = str_replace(
                'pour recevoir un lien vous permettant de t&eacute;l&eacute;charger toutes les photos de la soir&eacute;e',
                'pour acc&eacute;der &agrave; toutes les photos de la soir&eacute;e',
                $html,
            );
        }

        return $html;
    }

    private function importCities(Connection $legacy, SymfonyStyle $io): void
    {
        if ($this->cities->count([]) > 0) {
            $io->writeln('Villes : déjà présentes.');

            return;
        }

        $result = $legacy->executeQuery(
            "SELECT VILLE, CP, MIN(NOMADMIN1) AS region, MIN(NOMADMIN2) AS department, MIN(LATITUDE) AS latitude, MIN(LONGITUDE) AS longitude
             FROM discotek_autocomplete WHERE CODEPAYS = 'FR' GROUP BY VILLE, CP",
        );

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        $count = 0;
        $io->progressStart();
        while ($row = $result->fetchAssociative()) {
            $connection->insert('city', [
                'name' => $row['VILLE'],
                'search_name' => City::normalize((string) $row['VILLE']),
                'postal_code' => $row['CP'],
                'department' => (string) $row['department'],
                'region' => (string) $row['region'],
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
            ]);
            if (0 === ++$count % 1000) {
                $io->progressAdvance(1000);
            }
        }
        $connection->commit();
        $io->progressFinish();
        $io->writeln(\sprintf('Villes : %d importées.', $count));
    }

    private function importSubscribers(Connection $legacy, SymfonyStyle $io): void
    {
        $count = 0;
        foreach ($legacy->iterateAssociative('SELECT * FROM discotek_newsletter_abo ORDER BY newsletter_abo_id') as $row) {
            $email = mb_strtolower(trim((string) $row['newsletter_abo_email']));
            if ('' === $email || \count($this->validator->validate($email, new Email())) > 0) {
                continue;
            }

            $name = trim($row['newsletter_abo_prenom'].' '.$row['newsletter_abo_nom']) ?: null;
            $postalCode = (int) $row['newsletter_abo_cp'] > 0 ? str_pad((string) $row['newsletter_abo_cp'], 5, '0', STR_PAD_LEFT) : null;
            $this->newsletter->subscribe($email, NewsletterSubscription::SOURCE_LEGACY, $name, trim((string) $row['newsletter_abo_ville']) ?: null, $postalCode);
            // Flushing at each row makes the "already subscribed" lookup reliable for duplicates.
            $this->entityManager->flush();
            ++$count;
        }
        $io->writeln(\sprintf('Newsletter : %d adresses traitées.', $count));
    }

    private function importParties(Connection $legacy, SymfonyStyle $io): void
    {
        $rows = $legacy->fetchAllAssociative(
            'SELECT * FROM discotek_contenu WHERE contenu_cat_id LIKE :category ORDER BY contenu_id',
            ['category' => '%'.self::PHOTO_CATEGORY.'%'],
        );
        $imported = 0;
        $codes = [];

        foreach ($rows as $row) {
            $code = $this->cleaner->partyCodeFromMedia((string) $row['contenu_medias']);
            $slug = null === $code ? null : mb_strtolower($code);
            if (null === $slug || !preg_match('/^'.Party::SLUG_PATTERN.'$/', $slug) || isset($codes[$slug])) {
                continue; // No photo directory or duplicated party.
            }
            $codes[$slug] = true;

            if (null !== $this->parties->findOneBy(['legacyId' => (int) $row['contenu_id']]) || null !== $this->parties->findOneBy(['slug' => $slug])) {
                continue;
            }

            $party = (new Party())
                ->setTitle($this->cleaner->cleanTitle((string) $row['contenu_titre']))
                ->setSlug($slug)
                ->setAccessCode($slug)
                ->setPublished('1' === (string) $row['contenu_choix_activation'])
                ->setLegacyId((int) $row['contenu_id']);
            $this->entityManager->persist($party);
            ++$imported;
        }

        $this->entityManager->flush();
        $io->writeln(\sprintf('Soirées : %d importées.', $imported));
    }

    private function importPhotos(string $photosDir, SymfonyStyle $io): void
    {
        $photosDir = rtrim(str_replace('\\', '/', $photosDir), '/');
        foreach ($this->parties->findAll() as $party) {
            $directory = $photosDir.'/'.$party->getSlug();
            if (!is_dir($directory) || !$party->getPhotos()->isEmpty()) {
                continue;
            }

            $files = array_values(array_filter(
                scandir($directory) ?: [],
                static fn (string $file): bool => is_file($directory.'/'.$file) && (bool) preg_match('/\.(jpe?g|png|gif|webp)$/i', $file),
            ));

            $io->writeln(\sprintf('Photos de "%s" : %d fichiers', $party->getTitle(), \count($files)));
            $this->partyStorage->prepare($party->getSlug());
            $io->progressStart(\count($files));
            foreach ($files as $file) {
                $this->photoImporter->import($party, $directory.'/'.$file, $file);
                $io->progressAdvance();
            }
            $io->progressFinish();
        }
    }
}
