<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * Interface for country getter and setter
 */
interface I_Country_Aware
{
    /**
     * Country setter
     */
    public function set_country(\Oxid_Esales\Eshop\Application\Model\Country $o_country);
    /**
     * Country getter
     *
     * @return \OxidEsales\Eshop\Application\Model\Country
     */
    public function get_country();
}