<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GuestRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuestRange>
 */
class GuestRangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuestRange::class);
    }

    /** @return list<GuestRange> */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function createActiveQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.active = true')
            ->orderBy('g.position', 'ASC')
            ->addOrderBy('g.id', 'ASC');
    }
}
