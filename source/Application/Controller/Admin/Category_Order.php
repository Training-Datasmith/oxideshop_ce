<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article categories order manager.
 * There is possibility to change category sorting.
 * Admin Menu: Manage Products -> Categories -> Order.
 */
class Category_Order extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Loads article category ordering info, passes it to template engine
     * engine and returns name of template file "category_order".
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
        // resetting
        \Oxid_Esales\Eshop\Core\Registry::get_session()->set_variable('neworder_sess', null);
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_category->load($sox_id);
            //Disable editing for derived items
            if ($o_category->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_category_order_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Category_Order_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_category_order_ajax->get_columns();
            return 'popups/category_order';
        }
        return 'category_order';
    }
}