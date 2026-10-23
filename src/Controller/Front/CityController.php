<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\City;
use App\Repository\CityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class CityController extends AbstractController
{
    private const MIN_LENGTH = 2;

    #[Route('/api/villes', name: 'app_city_search', methods: ['GET'], priority: 10)]
    public function search(CityRepository $cities, #[MapQueryParameter] string $q = ''): JsonResponse
    {
        $results = mb_strlen(trim($q)) < self::MIN_LENGTH ? [] : array_map(
            static fn (City $city): array => [
                'name' => $city->getName(),
                'postalCode' => $city->getPostalCode(),
                'label' => $city->getLabel(),
                'latitude' => $city->getLatitude(),
                'longitude' => $city->getLongitude(),
            ],
            $cities->search($q, 20),
        );

        $response = $this->json($results);
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
