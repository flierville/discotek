<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Page;
use App\Enum\PageKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->findOneBy(['slug' => $slug, 'published' => true]);
    }

    public function findPublishedByKind(PageKind $kind): ?Page
    {
        return $this->findOneBy(['kind' => $kind, 'published' => true]);
    }

    public function findOneByKind(PageKind $kind): ?Page
    {
        return $this->findOneBy(['kind' => $kind]);
    }

    /** @return list<Page> */
    public function findMenuPages(): array
    {
        return $this->findBy(['published' => true, 'inMenu' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Page> */
    public function findFooterPages(): array
    {
        return $this->findBy(['published' => true, 'inFooter' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Page> */
    public function findPublished(): array
    {
        return $this->findBy(['published' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<Page> */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }
}
