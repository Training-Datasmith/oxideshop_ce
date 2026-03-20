<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Contract;

/**
 * Interface for selection list based objects
 */
interface I_Select_List
{
    /**
     * Returns selection list label
     *
     * @return string
     */
    public function get_label();
    /**
     * Returns array of oxSelection's
     *
     * @return array
     */
    public function get_selections();
    /**
     * Returns active selection object
     *
     * @return \OxidEsales\Eshop\Application\Model\Selection
     */
    public function get_active_selection();
}