<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * The interface methods should be implemented by classes which need a configuration object
 * (usually OxConfig) manually set.
 */
interface I_Configurable
{
    /**
     * Sets configuration object
     *
     * @param \OxidEsales\Eshop\Core\Config $oConfig Configraution object
     *
     * @abstract
     *
     * @return mixed
     */
    public function set_config(\Oxid_Esales\Eshop\Core\Config $o_config);
    /**
     * Returns active configuration object
     *
     * @abstract
     *
     * @return \OxidEsales\Eshop\Core\Config
     */
    public function get_config();
}