<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

class ProductSearchRequestFactory implements ProductSearchRequestFactoryInterface
{
    public function create(): ProductSearchRequestInterface
    {
        return new ProductSearchRequest();
    }
}
