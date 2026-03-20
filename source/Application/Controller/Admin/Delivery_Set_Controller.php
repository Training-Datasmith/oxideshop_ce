<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin article deliveryset manager.
 * Returns template, that arranges two other templates ("deliveryset_list"
 * and "deliveryset_main") to frame.
 * Admin Menu: Shop settings -> Shipping & Handling Sets.
 */
class Delivery_Set_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'deliveryset';
}