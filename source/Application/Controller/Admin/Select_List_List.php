<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin selectlist list manager.
 */
class Select_List_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxselectlist';
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'selectlist_list';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxtitle';
}