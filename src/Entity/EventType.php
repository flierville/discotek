<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Kind of event proposed in the quote form. A fixed price replaces the guest range price
 * (e.g. New Year's Eve).
 */
#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
class EventType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $label = '';

    /** Price in euros overriding the guest range price, null = no override. */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $fixedPrice = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

    public function __construct(string $label = '', ?int $fixedPrice = null, int $position = 0)
    {
        $this->label = $label;
        $this->fixedPrice = $fixedPrice;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = trim((string) $label);

        return $this;
    }

    public function getFixedPrice(): ?int
    {
        return $this->fixedPrice;
    }

    public function setFixedPrice(?int $fixedPrice): static
    {
        $this->fixedPrice = $fixedPrice;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = (int) $position;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
