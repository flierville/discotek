<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Site wide settings, a single row edited from the back office.
 */
#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Brand name used in emails and in the footer. */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $siteName = 'DiscoTek Animation';

    /** Default <title> and suffix of every page title. */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $siteTitle = 'Discotek-Animation - Disc Jockey Haute Normandie, Seine Maritime Rouen';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $publicEmail = 'disco-tek@live.fr';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = '06.64.95.10.09';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = '24 Rue du Vert Buisson - 76690 GRUGNY';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Indiquez au moins un destinataire.')]
    #[Assert\All([new Assert\NotBlank(), new Assert\Email()])]
    private array $contactRecipients = [];

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Indiquez au moins un destinataire.')]
    #[Assert\All([new Assert\NotBlank(), new Assert\Email()])]
    private array $quoteRecipients = [];

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Regex(pattern: '/^G-[A-Z0-9]+$/', message: 'Identifiant Google Analytics 4 attendu (G-XXXXXXX).')]
    private ?string $googleAnalyticsId = null;

    // --- Travel rules of the online quote -------------------------------------------------

    /** Departure point of the DJ (Grugny). */
    #[ORM\Column]
    #[Assert\Range(min: -90, max: 90)]
    private float $departureLatitude = 49.615395;

    #[ORM\Column]
    #[Assert\Range(min: -180, max: 180)]
    private float $departureLongitude = 1.103135;

    /** Bird flight distance multiplier to estimate the road distance. */
    #[ORM\Column]
    #[Assert\Range(min: 1, max: 3)]
    private float $roadFactor = 1.22;

    /** Up to this distance (km) the short trip fee applies. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $shortTripMaxKm = 20;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $shortTripFee = 20;

    /** Fee applied beyond the short trip distance, plus the price per extra km. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $longTripBaseFee = 40;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private float $longTripPricePerKm = 1.0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function setSiteName(?string $siteName): static
    {
        $this->siteName = trim((string) $siteName);

        return $this;
    }

    public function getSiteTitle(): string
    {
        return $this->siteTitle;
    }

    public function setSiteTitle(?string $siteTitle): static
    {
        $this->siteTitle = trim((string) $siteTitle);

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): static
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function getPublicEmail(): string
    {
        return $this->publicEmail;
    }

    public function setPublicEmail(?string $publicEmail): static
    {
        $this->publicEmail = trim((string) $publicEmail);

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /** @return list<string> */
    public function getContactRecipients(): array
    {
        return $this->contactRecipients;
    }

    /** @param iterable<?string> $recipients */
    public function setContactRecipients(iterable $recipients): static
    {
        $this->contactRecipients = self::normalizeEmails($recipients);

        return $this;
    }

    /** @return list<string> */
    public function getQuoteRecipients(): array
    {
        return $this->quoteRecipients;
    }

    /** @param iterable<?string> $recipients */
    public function setQuoteRecipients(iterable $recipients): static
    {
        $this->quoteRecipients = self::normalizeEmails($recipients);

        return $this;
    }

    public function getGoogleAnalyticsId(): ?string
    {
        return $this->googleAnalyticsId;
    }

    public function setGoogleAnalyticsId(?string $googleAnalyticsId): static
    {
        $this->googleAnalyticsId = $googleAnalyticsId ? trim($googleAnalyticsId) : null;

        return $this;
    }

    public function getDepartureLatitude(): float
    {
        return $this->departureLatitude;
    }

    public function setDepartureLatitude(?float $departureLatitude): static
    {
        $this->departureLatitude = (float) $departureLatitude;

        return $this;
    }

    public function getDepartureLongitude(): float
    {
        return $this->departureLongitude;
    }

    public function setDepartureLongitude(?float $departureLongitude): static
    {
        $this->departureLongitude = (float) $departureLongitude;

        return $this;
    }

    public function getRoadFactor(): float
    {
        return $this->roadFactor;
    }

    public function setRoadFactor(?float $roadFactor): static
    {
        $this->roadFactor = (float) $roadFactor;

        return $this;
    }

    public function getShortTripMaxKm(): int
    {
        return $this->shortTripMaxKm;
    }

    public function setShortTripMaxKm(?int $shortTripMaxKm): static
    {
        $this->shortTripMaxKm = (int) $shortTripMaxKm;

        return $this;
    }

    public function getShortTripFee(): int
    {
        return $this->shortTripFee;
    }

    public function setShortTripFee(?int $shortTripFee): static
    {
        $this->shortTripFee = (int) $shortTripFee;

        return $this;
    }

    public function getLongTripBaseFee(): int
    {
        return $this->longTripBaseFee;
    }

    public function setLongTripBaseFee(?int $longTripBaseFee): static
    {
        $this->longTripBaseFee = (int) $longTripBaseFee;

        return $this;
    }

    public function getLongTripPricePerKm(): float
    {
        return $this->longTripPricePerKm;
    }

    public function setLongTripPricePerKm(?float $longTripPricePerKm): static
    {
        $this->longTripPricePerKm = (float) $longTripPricePerKm;

        return $this;
    }

    /**
     * @param iterable<?string> $emails
     *
     * @return list<string>
     */
    private static function normalizeEmails(iterable $emails): array
    {
        $normalized = [];
        foreach ($emails as $email) {
            $email = mb_strtolower(trim((string) $email));
            if ('' !== $email) {
                $normalized[] = $email;
            }
        }

        return array_values(array_unique($normalized));
    }
}
