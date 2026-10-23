<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Geo\GeoPoint;
use App\Geo\Location;
use App\Repository\CityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * City input with autocomplete. The coordinates are filled by the JS autocomplete; when the
 * visitor typed the city without choosing in the list, the city is resolved from the database.
 */
final class CityAutocompleteType extends AbstractType
{
    public function __construct(private readonly CityRepository $cities)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'attr' => [
                    'maxlength' => 100,
                    'autocomplete' => 'off',
                    'placeholder' => 'Code postal ou nom de la ville',
                    'data-city-autocomplete-target' => 'input',
                    'data-action' => 'input->city-autocomplete#search keydown->city-autocomplete#navigate',
                ],
            ])
            ->add('postalCode', HiddenType::class, ['attr' => ['data-city-autocomplete-target' => 'postalCode']])
            ->add('latitude', HiddenType::class, ['attr' => ['data-city-autocomplete-target' => 'latitude']])
            ->add('longitude', HiddenType::class, ['attr' => ['data-city-autocomplete-target' => 'longitude']]);

        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?Location $location): array => null === $location ? [] : [
                'name' => $location->city,
                'postalCode' => $location->postalCode,
                'latitude' => (string) $location->point->latitude,
                'longitude' => (string) $location->point->longitude,
            ],
            fn (?array $value): ?Location => $this->toLocation($value ?? []),
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'data_class' => null,
            'error_bubbling' => false,
            'invalid_message' => 'Veuillez sélectionner une ville dans la liste proposée.',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'city_autocomplete';
    }

    /** @param array<string, ?string> $value */
    private function toLocation(array $value): ?Location
    {
        $name = trim((string) ($value['name'] ?? ''));
        if ('' === $name) {
            return null;
        }

        $postalCode = trim((string) ($value['postalCode'] ?? '')) ?: null;
        $latitude = $value['latitude'] ?? null;
        $longitude = $value['longitude'] ?? null;

        if (is_numeric($latitude) && is_numeric($longitude)) {
            try {
                return new Location($name, $postalCode, new GeoPoint((float) $latitude, (float) $longitude));
            } catch (\InvalidArgumentException) {
                // Tampered coordinates: fall back on the database lookup.
            }
        }

        $city = $this->cities->findBestMatch($name, $postalCode);
        if (null === $city) {
            throw new TransformationFailedException(\sprintf('Unknown city "%s".', $name));
        }

        return new Location($city->getName(), $city->getPostalCode(), new GeoPoint($city->getLatitude(), $city->getLongitude()));
    }
}
