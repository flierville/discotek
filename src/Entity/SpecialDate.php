<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SpecialDateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Recurring day of the year with a fixed price (24/12, 31/12...). Has priority over everything.
 */
#[ORM\Entity(repositoryClass: SpecialDateRepository::class)]
#[ORM\UniqueConstraint(name: 'special_date_day_month', columns: ['day', 'month'])]
#[UniqueEntity(fields: ['day', 'month'], message: 'Un tarif existe déjà pour cette date.', errorPath: 'day')]
class SpecialDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $label = '';

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 31)]
    private int $day = 1;

    #[ORM\Column(type: 'smallint')]
    #[Assert\Range(min: 1, max: 12)]
    private int $month = 1;

    /** Price in euros. */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    public function __construct(string $label = '', int $day = 1, int $month = 1, int $price = 0)
    {
        $this->label = $label;
        $this->day = $day;
        $this->month = $month;
        $this->price = $price;
    }

    #[Assert\Callback]
    public function validateCalendarDay(ExecutionContextInterface $context): void
    {
        // 2024 is a leap year: 29/02 is accepted.
        if (!checkdate($this->month, $this->day, 2024)) {
            $context->buildViolation("Cette date n'existe pas.")->atPath('day')->addViolation();
        }
    }

    public function matches(\DateTimeInterface $date): bool
    {
        return (int) $date->format('j') === $this->day && (int) $date->format('n') === $this->month;
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

    public function getDay(): int
    {
        return $this->day;
    }

    public function setDay(?int $day): static
    {
        $this->day = (int) $day;

        return $this;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function setMonth(?int $month): static
    {
        $this->month = (int) $month;

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
}
