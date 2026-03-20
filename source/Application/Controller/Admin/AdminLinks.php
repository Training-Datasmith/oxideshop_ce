<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admins links manager.
 * Sets template, that arranges two other templates ("adminlinks_lis"
 * and "adminlinks_main") to frame.
 * Admin Menu: Customer Info -> Links.
 */
class Admin_Links extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'admin_links';
}