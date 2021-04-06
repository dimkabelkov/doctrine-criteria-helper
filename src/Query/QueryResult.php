<?php

namespace Dimkabelkov\CriteriaHelper\Query;

class QueryResult
{
    /**
     * @var int
     */
    private ?int $prev;

    /**
     * @var int
     */
    private ?int $next;

    /**
     * @varint
     */
    private int $count;

    /**
     * @var array
     */
    private array $items;

    public function __construct(array $items, int $count, int $skip, int $limit)
    {
        $this->items = $items;
        $this->count = $count;

        $this->prev = $skip - $limit;
        if ($this->prev < 0) {
            $this->prev = null;
        }
        $this->next = $limit + $skip;
        if ($this->next > $count - 1) {
            $this->next = null;
        }
    }

    public function getPrev(): ?int
    {
        return $this->prev;
    }

    public function getNext(): ?int
    {
        return $this->next;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
