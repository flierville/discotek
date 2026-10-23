<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PartyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An evening ("soirée") whose photos are protected by an access code.
 * Its photos are stored in a directory named after the slug.
 */
#[ORM\Entity(repositoryClass: PartyRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Ce raccourci est déjà utilisé par une autre soirée.')]
#[UniqueEntity(fields: ['accessCode'], message: 'Ce code est déjà utilisé par une autre soirée.')]
class Party
{
    public const SLUG_PATTERN = '[a-z0-9]+(?:[-_][a-z0-9]+)*';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $title = '';

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    #[Assert\Regex(pattern: '/^'.self::SLUG_PATTERN.'$/', message: 'Uniquement des minuscules, chiffres, tirets et underscores.')]
    private string $slug = '';

    /** Stored lower case: visitors type it case insensitively. */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    #[Assert\Regex(pattern: '/^\S+$/', message: "Le code ne doit pas contenir d'espace.")]
    private string $accessCode = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column]
    private bool $published = true;

    #[ORM\Column(nullable: true, unique: true)]
    private ?int $legacyId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, PartyPhoto> */
    #[ORM\OneToMany(targetEntity: PartyPhoto::class, mappedBy: 'party', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['originalName' => 'ASC', 'id' => 'ASC'])]
    private Collection $photos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->photos = new ArrayCollection();
    }

    public static function normalizeCode(string $code): string
    {
        return mb_strtolower(trim($code));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = trim((string) $title);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = trim((string) $slug);

        return $this;
    }

    public function getAccessCode(): string
    {
        return $this->accessCode;
    }

    public function setAccessCode(?string $accessCode): static
    {
        $this->accessCode = self::normalizeCode((string) $accessCode);

        return $this;
    }

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeImmutable $eventDate): static
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    public function setLegacyId(?int $legacyId): static
    {
        $this->legacyId = $legacyId;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, PartyPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(PartyPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
        }

        return $this;
    }

    public function removePhoto(PartyPhoto $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    public function getCover(): ?PartyPhoto
    {
        return $this->photos->first() ?: null;
    }
}
