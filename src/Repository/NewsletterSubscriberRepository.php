<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscriber>
 */
class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    public function findOneByEmail(string $email): ?NewsletterSubscriber
    {
        return $this->findOneBy(['email' => mb_strtolower(trim($email))]);
    }

    public function createSearchQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')->orderBy('n.createdAt', 'DESC')->addOrderBy('n.id', 'DESC');

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('n.email LIKE :search OR n.name LIKE :search OR n.city LIKE :search')
                ->setParameter('search', '%'.addcslashes(trim($search), '%_').'%');
        }

        return $qb;
    }

    /** @return iterable<NewsletterSubscriber> */
    public function streamAll(): iterable
    {
        return $this->createQueryBuilder('n')->orderBy('n.email', 'ASC')->getQuery()->toIterable();
    }
}
