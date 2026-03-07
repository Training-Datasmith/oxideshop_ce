<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * @internal Please do not use or extend this class.
 */
class SortingValidator
{
    /**
     * @param string $sortBy
     * @param string $sortOrder
     */
    public function isValid($sortBy, $sortOrder): bool
    {
        if (
            $sortBy
            && $sortOrder
            && in_array(strtolower($sortOrder), $this->getSortingOrders())
            && in_array($sortBy, \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('aSortCols'))
        ) {
            return true;
        }

        return false;
    }

    public function getSortingOrders(): array
    {
        return ['desc', 'asc'];
    }
}
