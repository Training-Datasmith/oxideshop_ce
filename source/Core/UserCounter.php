<?php

declare(strict_types=1);

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Class used for counting users depending on given attributes.
 */
class UserCounter
{
    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     */
    public function getAdminCount(): int
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sQuery = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user'";

        return (int) $oDb->getOne($sQuery);
    }

    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     */
    public function getActiveAdminCount(): int
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sQuery = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user' AND oxactive = 1 ";

        return (int) $oDb->getOne($sQuery);
    }
}
