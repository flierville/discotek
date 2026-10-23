<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Geo\Location;
use Symfony\Component\Validator\Constraints as Assert;

final class PhotoAccessData
{
    #[Assert\NotBlank(message: 'Veuillez saisir votre nom.')]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Veuillez saisir votre adresse e-mail.')]
    #[Assert\Email(message: 'E-Mail invalide.')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotNull(message: 'Veuillez indiquer votre ville.')]
    public ?Location $location = null;

    #[Assert\NotBlank(message: 'Veuillez saisir le code de la soirée.')]
    #[Assert\Length(max: 50)]
    public ?string $code = null;

    public bool $newsletter = false;
}
