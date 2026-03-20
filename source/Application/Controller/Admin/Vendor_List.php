<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin vendor list manager.
 */
class Vendor_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'vendor_list';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxvendor';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxtitle';
}