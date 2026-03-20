<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin payment manager.
 * Returns template, that arranges two other templates ("payment_list"
 * and "payment_main") to frame.
 * Admin Menu: Shop Settings -> Payment Methods.
 */
class Admin_Payment extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'admin_payment';
}