<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    /**
     * Search by beginning of postal code or of city name (accents and dashes ignored).
     *
     * @return list<City>
     */
    public function search(string $term, int $limit = 30): array
    {
        $term = trim($term);
        // CEDEX postal codes (76190, 76191...) are fetched then merged, like the legacy GROUP BY VILLE, LEFT(CP, 3).
        $qb = $this->createQueryBuilder('c')->setMaxResults($limit * 5);

        if (ctype_digit($term)) {
            $qb->andWhere('c.postalCode LIKE :term')
                ->setParameter('term', $term.'%')
                ->orderBy('c.postalCode', 'ASC');
        } else {
            $qb->andWhere('c.searchName LIKE :term')
                ->setParameter('term', addcslashes(City::normalize($term), '%_').'%')
                ->orderBy('c.searchName', 'ASC')
                ->addOrderBy('c.postalCode', 'ASC');
        }

        $cities = [];
        foreach ($qb->getQuery()->getResult() as $city) {
            $cities[$city->getSearchName().'|'.substr($city->getPostalCode(), 0, 3)] ??= $city;
        }

        return \array_slice(array_values($cities), 0, $limit);
    }

    /**
     * Resolves a city typed without using the autocomplete list.
     */
    public function findBestMatch(string $name, ?string $postalCode): ?City
    {
        $criteria = ['searchName' => City::normalize($name)];
        if (null !== $postalCode && '' !== $postalCode) {
            $criteria['postalCode'] = $postalCode;
        }

        return $this->findOneBy($criteria, ['postalCode' => 'ASC']);
    }
}
