<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search\Event;

use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchRequestInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Search\ProductSearchResultInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AfterProductSearchEvent extends Event
{
    public function __construct(
        private readonly ProductSearchRequestInterface $searchRequest,
        private ProductSearchResultInterface $searchResult,
    ) {
    }

    public function getSearchRequest(): ProductSearchRequestInterface
    {
        return $this->searchRequest;
    }

    public function getSearchResult(): ProductSearchResultInterface
    {
        return $this->searchResult;
    }

    public function setSearchResult(ProductSearchResultInterface $searchResult): void
    {
        $this->searchResult = $searchResult;
    }
}
