<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class ContactData
{
    #[Assert\NotBlank(message: 'Veuillez saisir votre nom et/ou prénom.')]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Veuillez saisir votre adresse e-mail.')]
    #[Assert\Email(message: 'E-Mail invalide.')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Regex(pattern: '/^\+?[0-9 .\-]{10,20}$/', message: 'Veuillez saisir au moins 10 chiffres.')]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'Veuillez saisir votre message.')]
    #[Assert\Length(max: 5000)]
    public ?string $message = null;

    public bool $newsletter = false;
}
