<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * Voucher list manager.
 */
class Voucher_List extends \Oxid_Esales\Eshop\Core\Model\List_Model
{
    /**
     * Calls parent constructor
     */
    public function __construct()
    {
        parent::__construct('oxvoucher');
    }
}