<?php

declare(strict_types=1);

namespace App\Form\Front;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Form\Model\QuoteRequestData;
use App\Form\Type\CityAutocompleteType;
use App\Form\Type\HoneypotType;
use App\Repository\EventTypeRepository;
use App\Repository\GuestRangeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class QuoteRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName', TextType::class, ['label' => 'Votre Nom', 'attr' => ['maxlength' => 100, 'autocomplete' => 'family-name']])
            ->add('firstName', TextType::class, ['label' => 'Votre Prénom', 'required' => false, 'attr' => ['maxlength' => 100, 'autocomplete' => 'given-name']])
            ->add('email', EmailType::class, ['label' => 'Votre Mail', 'attr' => ['maxlength' => 180, 'autocomplete' => 'email']])
            ->add('newsletter', CheckboxType::class, ['label' => 'Inscription à la Newsletter', 'required' => false])
            ->add('phone', TelType::class, ['label' => 'Téléphone', 'attr' => ['maxlength' => 20, 'autocomplete' => 'tel']])
            ->add('postalAddress', TextareaType::class, ['label' => 'Adresse postale', 'required' => false, 'attr' => ['rows' => 4]])
            ->add('eventType', EntityType::class, [
                'label' => 'Votre demande porte sur',
                'class' => EventType::class,
                'choice_label' => 'label',
                'query_builder' => static fn (EventTypeRepository $repository) => $repository->createActiveQueryBuilder(),
                'placeholder' => '-- Type de demande --',
                'required' => false,
            ])
            ->add('guestRange', EntityType::class, [
                'label' => 'Nombre de personne',
                'class' => GuestRange::class,
                'choice_label' => 'label',
                'query_builder' => static fn (GuestRangeRepository $repository) => $repository->createActiveQueryBuilder(),
                'placeholder' => '-- Nombre de Personne --',
            ])
            ->add('location', CityAutocompleteType::class, [
                'label' => 'Lieu',
                'help' => 'Saisissez le code postal ou bien quelques lettres du nom de la ville',
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['min' => (new \DateTimeImmutable('today'))->format('Y-m-d')],
            ])
            ->add('details', TextareaType::class, [
                'label' => 'Autres précisions',
                'required' => false,
                'help' => 'Merci de nous donner le maximum de détails',
                'attr' => ['rows' => 8],
            ])
            ->add('website', HoneypotType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => QuoteRequestData::class]);
    }
}
