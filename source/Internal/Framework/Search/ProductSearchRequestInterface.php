<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

interface ProductSearchRequestInterface
{
    public function setSearchQuery(string $searchQuery): void;
    public function getSearchQuery(): ?string;

    public function addFilter(string $key, string $value): void;
    public function getFilter(string $key): ?string;
    public function getFilters(): array;

    public function setLimit(int $limit): void;
    public function getLimit(): int;
    public function setOffset(int $offset): void;
    public function getOffset(): int;

    public function addSorting(Sorting $sorting): void;
    public function getSorting(): array;
}
