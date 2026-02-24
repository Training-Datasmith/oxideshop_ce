<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Domain\Product\Search\Event;

use OxidEsales\EshopCommunity\Internal\Domain\Product\Search\ProductSearchCriteria;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeProductSearchEvent extends Event
{
    public function __construct(
        private readonly ProductSearchCriteria $criteria,
        private readonly array $context,
    ) {
    }

    public function getCriteria(): ProductSearchCriteria
    {
        return $this->criteria;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
