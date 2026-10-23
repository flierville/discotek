<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuoteRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Online quote sent to a visitor. Labels and amounts are copied (snapshot) so that
 * changing the quote rules later never alters the history.
 */
#[ORM\Entity(repositoryClass: QuoteRequestRepository::class)]
#[ORM\Index(name: 'quote_request_created_at', columns: ['created_at'])]
class QuoteRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 30)]
    private string $phone;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $postalAddress;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $eventType;

    #[ORM\Column(length: 100)]
    private string $guestRange;

    #[ORM\Column(length: 180)]
    private string $city;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $eventDate;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details;

    #[ORM\Column]
    private bool $newsletter;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress;

    #[ORM\Column]
    private int $distanceKm;

    #[ORM\Column]
    private int $servicePrice;

    #[ORM\Column]
    private int $travelPrice;

    #[ORM\Column]
    private int $totalPrice;

    /** Human readable explanation of the applied pricing rule. */
    #[ORM\Column(length: 255)]
    private string $pricingRule;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct(
        string $lastName,
        ?string $firstName,
        string $email,
        string $phone,
        ?string $postalAddress,
        ?string $eventType,
        string $guestRange,
        string $city,
        ?string $postalCode,
        \DateTimeImmutable $eventDate,
        ?string $details,
        bool $newsletter,
        ?string $ipAddress,
        int $distanceKm,
        int $servicePrice,
        int $travelPrice,
        string $pricingRule,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->email = $email;
        $this->phone = $phone;
        $this->postalAddress = $postalAddress;
        $this->eventType = $eventType;
        $this->guestRange = $guestRange;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->eventDate = $eventDate;
        $this->details = $details;
        $this->newsletter = $newsletter;
        $this->ipAddress = $ipAddress;
        $this->distanceKm = $distanceKm;
        $this->servicePrice = $servicePrice;
        $this->travelPrice = $travelPrice;
        $this->totalPrice = $servicePrice + $travelPrice;
        $this->pricingRule = $pricingRule;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getFullName(): string
    {
        return trim($this->lastName.' '.$this->firstName);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPostalAddress(): ?string
    {
        return $this->postalAddress;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function getGuestRange(): string
    {
        return $this->guestRange;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getEventDate(): \DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function isNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getDistanceKm(): int
    {
        return $this->distanceKm;
    }

    public function getServicePrice(): int
    {
        return $this->servicePrice;
    }

    public function getTravelPrice(): int
    {
        return $this->travelPrice;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function getPricingRule(): string
    {
        return $this->pricingRule;
    }

    public function isRead(): bool
    {
        return null !== $this->readAt;
    }

    public function markAsRead(): void
    {
        $this->readAt ??= new \DateTimeImmutable();
    }
}
