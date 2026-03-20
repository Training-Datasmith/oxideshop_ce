<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin category main attributes manager.
 * There is possibility to change attribute description, assign categories to
 * this attribute, etc.
 * Admin Menu: Manage Products -> Attributes -> Gruppen.
 */
class Attribute_Category extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_attr = ox_new(\Oxid_Esales\Eshop\Application\Model\Attribute::class);
            $o_attr->load($sox_id);
            $this->_a_view_data['edit'] = $o_attr;
        }
        if (Registry::get_request()->get_request_escaped_parameter('aoc')) {
            $o_attribute_category_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Attribute_Category_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_attribute_category_ajax->get_columns();
            return 'popups/attribute_category';
        }
        return 'attribute_category';
    }
}