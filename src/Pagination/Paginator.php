<?php

declare(strict_types=1);

namespace App\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/**
 * Minimal page based pagination of a Doctrine query.
 *
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Paginator implements \IteratorAggregate, \Countable
{
    public const PER_PAGE = 25;

    /** @var list<T> */
    private array $items;
    private int $total;

    public function __construct(QueryBuilder $queryBuilder, private readonly int $page = 1, private readonly int $perPage = self::PER_PAGE)
    {
        $query = $queryBuilder
            ->setFirstResult(($this->page - 1) * $this->perPage)
            ->setMaxResults($this->perPage)
            ->getQuery();

        $paginator = new DoctrinePaginator($query, fetchJoinCollection: false);
        $this->total = \count($paginator);
        $this->items = iterator_to_array($paginator, false);
    }

    public static function fromRequestPage(QueryBuilder $queryBuilder, mixed $page, int $perPage = self::PER_PAGE): self
    {
        return new self($queryBuilder, max(1, (int) $page), $perPage);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageCount(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->getPageCount();
    }
}
