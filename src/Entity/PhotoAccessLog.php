<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PhotoAccessLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Who opened a party gallery (the form asks name, email, city and code).
 */
#[ORM\Entity(repositoryClass: PhotoAccessLogRepository::class)]
class PhotoAccessLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Party $party,
        #[ORM\Column(length: 100)]
        private string $name,
        #[ORM\Column(length: 180)]
        private string $email,
        #[ORM\Column(length: 180)]
        private string $city,
        #[ORM\Column(length: 10, nullable: true)]
        private ?string $postalCode,
        #[ORM\Column]
        private bool $newsletter,
        #[ORM\Column(length: 45, nullable: true)]
        private ?string $ipAddress,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function isNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
}
