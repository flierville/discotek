<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\SpecialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SpecialDateFormType extends AbstractType
{
    private const MONTHS = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, ['label' => 'Libellé', 'attr' => ['placeholder' => 'Réveillon de la Saint-Sylvestre']])
            ->add('day', ChoiceType::class, ['label' => 'Jour', 'choices' => array_combine(range(1, 31), range(1, 31))])
            ->add('month', ChoiceType::class, ['label' => 'Mois', 'choices' => array_combine(self::MONTHS, range(1, 12))])
            ->add('price', IntegerType::class, ['label' => 'Prix de la prestation (€)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SpecialDate::class]);
    }
}
