<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GuestRangeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Quote rule: base price of the music entertainment according to the number of guests.
 */
#[ORM\Entity(repositoryClass: GuestRangeRepository::class)]
class GuestRange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $label = '';

    /** Price in euros. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

    public function __construct(string $label = '', int $price = 0, int $position = 0)
    {
        $this->label = $label;
        $this->price = $price;
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

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = (int) $price;

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
