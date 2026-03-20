<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin admin_pricealarm manager.
 * Returns template, that arranges two other templates ("apricealarm_list"
 * and "pricealarm_main") to frame.
 * Admin Menu: Customer Info -> admin_pricealarm.
 */
class Admin_Price_Alarm extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Default active tab number
     *
     * @var int
     */
    protected $_i_def_edit = 1;
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'admin_pricealarm';
}