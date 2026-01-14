<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeProductSearchEvent extends Event
{
    public function __construct(
        private ProductSearchRequestInterface $searchRequest,
    ) {
    }

    public function getSearchRequest(): ProductSearchRequestInterface
    {
        return $this->searchRequest;
    }

    public function setSearchRequest(ProductSearchRequestInterface $searchRequest): void
    {
        $this->searchRequest = $searchRequest;
    }
}
