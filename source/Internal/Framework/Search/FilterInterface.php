<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

interface FilterInterface
{
    public function getField(): string;

    /** @return list<string> */
    public function getValues(): array;
}
