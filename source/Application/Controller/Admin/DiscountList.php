<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin discount list manager.
 * Collects delivery base information (description), there is ability to
 * filter them by description, title or delete them.
 * Admin Menu: Shop Settings -> Discounts.
 */
class Discount_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'discount_list';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxdiscount';
    /**
     * Type of list.
     *
     * @var string
     */
    protected $_s_list_type = 'oxdiscountlist';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxsort';
}