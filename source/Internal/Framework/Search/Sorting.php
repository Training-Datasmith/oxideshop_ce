<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Search;

readonly class Sorting
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    public function __construct(
        public string $field,
        public string $direction = self::ASC,
    ) {
    }
}
