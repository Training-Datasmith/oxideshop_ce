<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Event;

use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchCriteria;
use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchResult;
use Symfony\Contracts\EventDispatcher\Event;

class AfterProductSearchEvent extends Event
{
    public function __construct(
        private readonly ProductSearchCriteria $criteria,
        private readonly ProductSearchResult $result,
        private readonly array $context,
    ) {
    }

    public function getCriteria(): ProductSearchCriteria
    {
        return $this->criteria;
    }

    public function getResult(): ProductSearchResult
    {
        return $this->result;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
