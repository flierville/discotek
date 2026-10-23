<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Party;
use App\Entity\PartyPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Party>
 */
class PartyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Party::class);
    }

    public function findPublishedByCode(string $code): ?Party
    {
        return $this->findOneBy(['accessCode' => Party::normalizeCode($code), 'published' => true]);
    }

    public function findPublishedBySlug(string $slug): ?Party
    {
        return $this->findOneBy(['slug' => $slug, 'published' => true]);
    }

    /**
     * Rows: [0 => Party, 'photoCount' => int].
     */
    public function createListQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('(SELECT COUNT(ph.id) FROM '.PartyPhoto::class.' ph WHERE ph.party = p) AS photoCount')
            ->orderBy('p.eventDate', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC');

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('p.title LIKE :search OR p.slug LIKE :search OR p.accessCode LIKE :search')
                ->setParameter('search', '%'.addcslashes(trim($search), '%_').'%');
        }

        return $qb;
    }

    public function countPhotos(): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(ph.id) FROM '.PartyPhoto::class.' ph')
            ->getSingleScalarResult();
    }
}
