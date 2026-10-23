<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\EventType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
            ->add('fixedPrice', IntegerType::class, [
                'label' => 'Prix fixe (€)',
                'required' => false,
                'help' => 'Optionnel : remplace le prix selon le nombre de personnes (ex. réveillon).',
            ])
            ->add('position', IntegerType::class, ['label' => 'Ordre dans la liste'])
            ->add('active', CheckboxType::class, ['label' => 'Proposé dans le formulaire', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => EventType::class]);
    }
}
