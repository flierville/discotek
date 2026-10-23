<?php

declare(strict_types=1);

namespace App\Form\Front;

use App\Form\Model\PhotoAccessData;
use App\Form\Type\CityAutocompleteType;
use App\Form\Type\HoneypotType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PhotoAccessType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Votre Nom', 'attr' => ['maxlength' => 100, 'autocomplete' => 'name']])
            ->add('email', EmailType::class, ['label' => 'Votre Mail', 'attr' => ['maxlength' => 180, 'autocomplete' => 'email']])
            ->add('newsletter', CheckboxType::class, ['label' => 'Inscription à la Newsletter', 'required' => false])
            ->add('location', CityAutocompleteType::class, [
                'label' => 'Votre Ville',
                'help' => 'Saisissez le code postal ou bien quelques lettres du nom de la ville',
            ])
            ->add('code', TextType::class, [
                'label' => 'Code de la soirée',
                'help' => 'Saisissez le code qui vous a été confié lors de la soirée',
                'attr' => ['maxlength' => 50, 'autocomplete' => 'off', 'autocapitalize' => 'none'],
            ])
            ->add('website', HoneypotType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PhotoAccessData::class]);
    }
}
