<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Party;
use App\Form\EventListener\SlugFromTitleListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PartyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la soirée',
                'attr' => ['placeholder' => 'Mariage de Julie et David', 'data-slug-target' => 'source', 'data-action' => 'slug#sync'],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Raccourci URL',
                'help' => 'Nom du répertoire des photos et adresse de la galerie (/photos-de-soirees/raccourci).',
                'attr' => ['placeholder' => 'mariage-julie-david', 'data-slug-target' => 'slug', 'data-action' => 'slug#lock'],
            ])
            ->add('accessCode', TextType::class, [
                'label' => 'Code soirée',
                'help' => 'Code unique à communiquer aux invités, sans espace (majuscules/minuscules indifférentes).',
                'attr' => ['data-access-code-target' => 'input', 'autocomplete' => 'off'],
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Date de la soirée',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('published', CheckboxType::class, [
                'label' => 'Galerie accessible aux invités',
                'required' => false,
            ])
            ->addEventSubscriber(new SlugFromTitleListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Party::class]);
    }
}
