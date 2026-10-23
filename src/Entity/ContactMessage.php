<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Log of every message sent with the contact form.
 */
#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Index(name: 'contact_message_created_at', columns: ['created_at'])]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct(
        #[ORM\Column(length: 100)]
        private string $name,
        #[ORM\Column(length: 180)]
        private string $email,
        #[ORM\Column(length: 30, nullable: true)]
        private ?string $phone,
        #[ORM\Column(type: Types::TEXT)]
        private string $message,
        #[ORM\Column]
        private bool $newsletter = false,
        #[ORM\Column(length: 45, nullable: true)]
        private ?string $ipAddress = null,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
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
