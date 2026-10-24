<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EventPricing;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Kind of event proposed in the quote form. It can replace the guest range price by a fixed price
 * (e.g. New Year's Eve) or add a bonus / malus to it.
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

    #[ORM\Column(length: 20, enumType: EventPricing::class, options: ['default' => 'guest_range'])]
    private EventPricing $pricing = EventPricing::GuestRange;

    /** Price in euros replacing the guest range price (pricing = fixed). */
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $fixedPrice = null;

    /** Amount in euros added to (or, when negative, removed from) the guest range price (pricing = adjustment). */
    #[ORM\Column(nullable: true)]
    private ?int $priceAdjustment = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $active = true;

    public function __construct(string $label = '', ?int $fixedPrice = null, int $position = 0)
    {
        $this->label = $label;
        $this->fixedPrice = $fixedPrice;
        $this->position = $position;
        if (null !== $fixedPrice) {
            $this->pricing = EventPricing::Fixed;
        }
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

    public function getPricing(): EventPricing
    {
        return $this->pricing;
    }

    public function setPricing(EventPricing $pricing): static
    {
        $this->pricing = $pricing;

        return $this;
    }

    /** The fixed price when it applies, null otherwise. */
    public function getFixedPrice(): ?int
    {
        return EventPricing::Fixed === $this->pricing ? $this->fixedPrice : null;
    }

    public function setFixedPrice(?int $fixedPrice): static
    {
        $this->fixedPrice = $fixedPrice;

        return $this;
    }

    /** The bonus / malus when it applies, null otherwise. */
    public function getPriceAdjustment(): ?int
    {
        return EventPricing::Adjustment === $this->pricing ? $this->priceAdjustment : null;
    }

    public function setPriceAdjustment(?int $priceAdjustment): static
    {
        $this->priceAdjustment = $priceAdjustment;

        return $this;
    }

    #[Assert\Callback]
    public function validatePricing(ExecutionContextInterface $context): void
    {
        if (EventPricing::Fixed === $this->pricing && null === $this->fixedPrice) {
            $context->buildViolation('Indiquez le prix fixe.')->atPath('fixedPrice')->addViolation();
        }

        if (EventPricing::Adjustment === $this->pricing && null === $this->priceAdjustment) {
            $context->buildViolation('Indiquez le montant du bonus / malus.')->atPath('priceAdjustment')->addViolation();
        }
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
