<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PageKind;
use App\Repository\PageRepository;
use App\Validator\UniquePageKind;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Cette adresse est déjà utilisée par une autre page.')]
#[UniquePageKind]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $title = '';

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Uniquement des minuscules, chiffres et tirets.')]
    private string $slug = '';

    #[ORM\Column(length: 30, enumType: PageKind::class)]
    private PageKind $kind = PageKind::Standard;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    /** <title> of the page, falls back on the title. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column]
    private bool $published = true;

    #[ORM\Column]
    private bool $inMenu = false;

    #[ORM\Column]
    private bool $inFooter = false;

    #[ORM\Column]
    private int $position = 0;

    /** Optional CSS color of the menu entry (legacy "color=#ff0000"). */
    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG)]
    private ?string $menuColor = null;

    /** Identifier in the old PHP site, used to redirect old URLs (?pageid=). */
    #[ORM\Column(nullable: true, unique: true)]
    private ?int $legacyId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getKind(): PageKind
    {
        return $this->kind;
    }

    public function setKind(PageKind $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function isHome(): bool
    {
        return PageKind::Home === $this->kind;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = (string) $content;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getBrowserTitle(): string
    {
        return $this->metaTitle ?: $this->title;
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

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function isInMenu(): bool
    {
        return $this->inMenu;
    }

    public function setInMenu(bool $inMenu): static
    {
        $this->inMenu = $inMenu;

        return $this;
    }

    public function isInFooter(): bool
    {
        return $this->inFooter;
    }

    public function setInFooter(bool $inFooter): static
    {
        $this->inFooter = $inFooter;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getMenuColor(): ?string
    {
        return $this->menuColor;
    }

    public function setMenuColor(?string $menuColor): static
    {
        $this->menuColor = $menuColor ?: null;

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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
