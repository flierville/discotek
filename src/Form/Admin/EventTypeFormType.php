<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\EventType;
use App\Enum\EventPricing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EventTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, ['label' => 'Libellé', 'attr' => ['placeholder' => 'Mariage']])
            ->add('pricing', EnumType::class, [
                'label' => 'Calcul du prix',
                'class' => EventPricing::class,
                'choice_label' => static fn (EventPricing $pricing): string => $pricing->label(),
                'attr' => ['data-action' => 'event-pricing#toggle', 'data-event-pricing-target' => 'mode'],
            ])
            ->add('fixedPrice', IntegerType::class, [
                'label' => 'Prix fixe (€)',
                'required' => false,
                'help' => 'Remplace le prix selon le nombre de personnes (ex. réveillon).',
                'row_attr' => ['data-event-pricing-target' => 'field', 'data-pricing' => EventPricing::Fixed->value],
            ])
            ->add('priceAdjustment', IntegerType::class, [
                'label' => 'Bonus / malus (€)',
                'required' => false,
                'help' => 'Ajouté au prix selon le nombre de personnes. Un montant négatif est retiré (ex. -50).',
                'row_attr' => ['data-event-pricing-target' => 'field', 'data-pricing' => EventPricing::Adjustment->value],
            ])
            ->add('position', IntegerType::class, ['label' => 'Ordre dans la liste'])
            ->add('active', CheckboxType::class, ['label' => 'Proposé dans le formulaire', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventType::class,
            'attr' => ['data-controller' => 'event-pricing'],
        ]);
    }
}
