<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SpecialDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpecialDate>
 */
class SpecialDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecialDate::class);
    }

    /** @return list<SpecialDate> */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['month' => 'ASC', 'day' => 'ASC']);
    }

    public function findOneForDate(\DateTimeInterface $date): ?SpecialDate
    {
        return $this->findOneBy(['day' => (int) $date->format('j'), 'month' => (int) $date->format('n')]);
    }
}
