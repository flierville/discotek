<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QuoteRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuoteRequest>
 */
class QuoteRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuoteRequest::class);
    }

    public function createSearchQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('q')->orderBy('q.createdAt', 'DESC');

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('q.lastName LIKE :search OR q.firstName LIKE :search OR q.email LIKE :search OR q.city LIKE :search OR q.phone LIKE :search')
                ->setParameter('search', '%'.addcslashes(trim($search), '%_').'%');
        }

        return $qb;
    }

    public function countUnread(): int
    {
        return $this->count(['readAt' => null]);
    }

    public function countSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->andWhere('q.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<QuoteRequest> */
    public function findLatest(int $limit): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
