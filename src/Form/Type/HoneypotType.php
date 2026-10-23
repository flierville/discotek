<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Blank;

/**
 * Invisible field: humans leave it empty, spam bots fill it.
 * Replaces the image captcha of the legacy site.
 */
final class HoneypotType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'label' => 'Laissez ce champ vide',
            'constraints' => [new Blank(message: 'Votre message a été identifié comme indésirable.')],
            'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'honeypot';
    }
}
