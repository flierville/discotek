<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    public function createSearchQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.createdAt', 'DESC');

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('c.name LIKE :search OR c.email LIKE :search OR c.message LIKE :search')
                ->setParameter('search', '%'.addcslashes(trim($search), '%_').'%');
        }

        return $qb;
    }

    public function countUnread(): int
    {
        return $this->count(['readAt' => null]);
    }

    /** @return list<ContactMessage> */
    public function findLatest(int $limit): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
