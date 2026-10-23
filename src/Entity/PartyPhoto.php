<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PartyPhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartyPhotoRepository::class)]
#[ORM\UniqueConstraint(name: 'party_photo_filename', columns: ['party_id', 'filename'])]
class PartyPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'photos')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Party $party,
        /** File name inside the party directory. */
        #[ORM\Column(length: 150)]
        private string $filename,
        #[ORM\Column(length: 255)]
        private string $originalName,
        #[ORM\Column]
        private int $width,
        #[ORM\Column]
        private int $height,
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $party->addPhoto($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
