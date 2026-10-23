<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;

final class ChangePasswordFormType extends AbstractType
{
    public const MIN_LENGTH = 10;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'constraints' => [new UserPassword(message: 'Mot de passe actuel incorrect.')],
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'first_options' => ['label' => 'Nouveau mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirmation', 'attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new NotBlank(),
                    new Length(min: self::MIN_LENGTH, minMessage: 'Au moins {{ limit }} caractères.'),
                    new NotCompromisedPassword(skipOnError: true),
                ],
            ]);
    }
}
