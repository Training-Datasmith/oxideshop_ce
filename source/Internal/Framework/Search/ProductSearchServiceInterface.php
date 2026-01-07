<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

interface ProductSearchServiceInterface
{
    public function search(ProductSearchRequestInterface $request): ProductSearchResultInterface;
}
