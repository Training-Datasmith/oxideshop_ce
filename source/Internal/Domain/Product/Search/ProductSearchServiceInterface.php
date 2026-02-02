<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search;

interface ProductSearchServiceInterface
{
    public function search(ProductSearchCriteria $criteria, array $context = []): ProductSearchResult;
}
