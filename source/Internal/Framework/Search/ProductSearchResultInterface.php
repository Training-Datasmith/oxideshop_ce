<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

interface ProductSearchResultInterface
{
    public function getProducts(): array;

    public function getTotalResults(): int;
}
