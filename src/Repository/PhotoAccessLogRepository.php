<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Party;
use App\Entity\PhotoAccessLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PhotoAccessLog>
 */
class PhotoAccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoAccessLog::class);
    }

    /** @return list<PhotoAccessLog> */
    public function findLatestForParty(Party $party, int $limit = 50): array
    {
        return $this->findBy(['party' => $party], ['createdAt' => 'DESC'], $limit);
    }

    public function countForParty(Party $party): int
    {
        return $this->count(['party' => $party]);
    }
}
