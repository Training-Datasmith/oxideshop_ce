<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article main delivery manager.
 * There is possibility to change delivery name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class Delivery_Articles extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            $this->create_category_tree('artcattree');
            // load object
            $o_delivery = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery::class);
            $o_delivery->load($sox_id);
            $this->_a_view_data['edit'] = $o_delivery;
            //Disable editing for derived articles
            if ($o_delivery->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_delivery_articles_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Articles_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_delivery_articles_ajax->get_columns();
            return 'popups/delivery_articles';
        }
        if ($i_aoc == 2) {
            $o_delivery_categories_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Categories_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_delivery_categories_ajax->get_columns();
            return 'popups/delivery_categories';
        }
        return 'delivery_articles';
    }
}