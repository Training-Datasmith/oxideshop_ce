<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin voucherserie manager.
 * Returns template, that arranges two other templates ("voucherserie_list"
 * and "voucherserie_main") to frame.
 * Admin Menu: Shop Settings -> Vouchers.
 */
class Voucher_Serie_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'voucherserie';
}