<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

readonly class ProductSearchResult implements ProductSearchResultInterface
{
    public function __construct(
        private array $products,
    ) {
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getTotalResults(): int
    {
        return count($this->products);
    }
}
