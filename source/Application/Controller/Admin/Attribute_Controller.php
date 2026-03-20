<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Returns template, that arranges two other templates ("attribute_list"
 * and "attribute_main") to frame.
 * Admin Menu: Manage Products -> Attributes.
 */
class Attribute_Controller extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'attribute';
}