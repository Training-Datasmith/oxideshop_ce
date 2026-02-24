<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Dao;

use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchCriteria;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchResult;

interface ProductSearchDaoInterface
{
    /** @param list<string> $searchColumns */
    public function findProducts(
        ProductSearchCriteria $criteria,
        int $language,
        array $searchColumns,
        bool $useAndLogic,
    ): ProductSearchResult;
}
