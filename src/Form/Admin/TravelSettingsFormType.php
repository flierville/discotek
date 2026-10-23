<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Travel fee rules of the online quote.
 */
final class TravelSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departureLatitude', NumberType::class, ['label' => 'Latitude du point de départ', 'scale' => 6, 'html5' => true, 'attr' => ['step' => 'any']])
            ->add('departureLongitude', NumberType::class, ['label' => 'Longitude du point de départ', 'scale' => 6, 'html5' => true, 'attr' => ['step' => 'any']])
            ->add('roadFactor', NumberType::class, [
                'label' => 'Coefficient route',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
                'help' => 'Distance à vol d\'oiseau multipliée par ce coefficient pour estimer la distance par la route.',
            ])
            ->add('shortTripMaxKm', IntegerType::class, ['label' => 'Distance du petit déplacement (km)', 'help' => 'Jusqu\'à cette distance, le forfait petit déplacement s\'applique.'])
            ->add('shortTripFee', IntegerType::class, ['label' => 'Forfait petit déplacement (€)'])
            ->add('longTripBaseFee', IntegerType::class, ['label' => 'Forfait au-delà (€)'])
            ->add('longTripPricePerKm', NumberType::class, [
                'label' => 'Prix par km supplémentaire (€)',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Settings::class]);
    }
}
