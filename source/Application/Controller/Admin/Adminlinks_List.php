<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

/**
 * Admin links collection.
 * Collects list of admin links. Links may be viewed by language, sorted by date,
 * url or any keyword.
 * Admin Menu: Customer Info -> Links.
 */
class Adminlinks_List extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_List_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'adminlinks_list';
    /**
     * Name of chosen object class (default null).
     *
     * @var string
     */
    protected $_s_list_class = 'oxlinks';
    /**
     * Default SQL sorting parameter (default null).
     *
     * @var string
     */
    protected $_s_def_sort_field = 'oxinsert';
    /**
     * Returns sorting fields array
     *
     * @return array
     */
    public function get_list_sorting()
    {
        $a_sorting = parent::get_list_sorting();
        if (isset($a_sorting['oxlinks'][$this->_s_def_sort_field])) {
            $this->_bl_desc = true;
        }
        return $a_sorting;
    }
}