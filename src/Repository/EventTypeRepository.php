<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventType>
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    /** @return list<EventType> */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function createActiveQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.active = true')
            ->orderBy('e.position', 'ASC')
            ->addOrderBy('e.id', 'ASC');
    }
}
