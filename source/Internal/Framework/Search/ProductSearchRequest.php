<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

class ProductSearchRequest implements ProductSearchRequestInterface
{
    private ?string $searchQuery = null;
    private array $filters = [];
    private int $limit = 10;
    private int $offset = 0;
    private array $sorting = [];

    public function setSearchQuery(string $searchQuery): void
    {
        $this->searchQuery = $searchQuery;
    }

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function addFilter(string $key, string $value): void
    {
        $this->filters[$key] = $value;
    }

    public function getFilter(string $key): ?string
    {
        return $this->filters[$key] ?? null;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function addSorting(Sorting $sorting): void
    {
        $this->sorting[] = $sorting;
    }

    public function getSorting(): array
    {
        return $this->sorting;
    }
}
