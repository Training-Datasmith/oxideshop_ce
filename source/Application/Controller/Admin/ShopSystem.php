<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin shop system setting manager.
 * Collects shop system settings, updates it on user submit, etc.
 * Admin Menu: Main Menu -> Core Settings -> System.
 */
class Shop_System extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'shop_system';
}