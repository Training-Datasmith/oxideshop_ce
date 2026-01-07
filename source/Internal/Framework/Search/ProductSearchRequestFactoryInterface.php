<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

interface ProductSearchRequestFactoryInterface
{
    public function create(): ProductSearchRequestInterface;
}
