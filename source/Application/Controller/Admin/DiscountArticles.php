<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article main discount manager.
 * There is possibility to change discount name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class Discount_Articles extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_discount = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
            $o_discount->load($sox_id);
            $this->_a_view_data['edit'] = $o_discount;
            //disabling derived items
            if ($o_discount->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
            // generating category tree for artikel choose select list
            $this->create_category_tree('artcattree');
        }
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_discount_articles_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Discount_Articles_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_discount_articles_ajax->get_columns();
            return 'popups/discount_articles';
        }
        if ($i_aoc == 2) {
            $o_discount_categories_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Discount_Categories_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_discount_categories_ajax->get_columns();
            return 'popups/discount_categories';
        }
        return 'discount_articles';
    }
}