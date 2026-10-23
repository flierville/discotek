<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Geo\Location;
use Symfony\Component\Validator\Constraints as Assert;

final class QuoteRequestData
{
    #[Assert\NotBlank(message: 'Veuillez saisir votre nom.')]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Veuillez saisir votre adresse e-mail.')]
    #[Assert\Email(message: 'E-Mail invalide.')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Numéro de téléphone obligatoire.')]
    #[Assert\Regex(pattern: '/^\+?[0-9 .\-]{10,20}$/', message: 'Veuillez saisir au moins 10 chiffres.')]
    public ?string $phone = null;

    #[Assert\Length(max: 500)]
    public ?string $postalAddress = null;

    public ?EventType $eventType = null;

    #[Assert\NotNull(message: 'Veuillez choisir le nombre de personnes.')]
    public ?GuestRange $guestRange = null;

    #[Assert\NotNull(message: 'Veuillez indiquer le lieu de la soirée.')]
    public ?Location $location = null;

    #[Assert\NotNull(message: 'Veuillez choisir la date.')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date doit être dans le futur.')]
    public ?\DateTimeImmutable $eventDate = null;

    #[Assert\Length(max: 5000)]
    public ?string $details = null;

    public bool $newsletter = false;
}
