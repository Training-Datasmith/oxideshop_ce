<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
/**
 * Admin article main delivery manager.
 * There is possibility to change delivery name, article, user
 * and etc.
 * Admin Menu: Shop settings -> Shipping & Handling -> Main.
 */
class Delivery_Users extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
        $s_view_name = $table_view_name_generator->get_view_name('oxgroups', $this->_i_edit_lang);
        // all usergroups
        $o_groups = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
        $o_groups->init('oxgroups');
        $o_groups->select_string("select * from {$s_view_name}");
        $o_root = new \Oxid_Esales\Eshop\Application\Model\Groups();
        $o_root->oxgroups__oxid = new \Oxid_Esales\Eshop\Core\Field('');
        $o_root->oxgroups__oxtitle = new \Oxid_Esales\Eshop\Core\Field('-- ');
        // rebuild list as we need the "no value" entry at the first position
        $a_new_list = [];
        $a_new_list[] = $o_root;
        foreach ($o_groups as $val) {
            $a_new_list[$val->oxgroups__oxid->value] = new \Oxid_Esales\Eshop\Application\Model\Groups();
            $a_new_list[$val->oxgroups__oxid->value]->oxgroups__oxid = new \Oxid_Esales\Eshop\Core\Field($val->oxgroups__oxid->value);
            $a_new_list[$val->oxgroups__oxid->value]->oxgroups__oxtitle = new \Oxid_Esales\Eshop\Core\Field($val->oxgroups__oxtitle->value);
        }
        $o_groups = $a_new_list;
        if (isset($sox_id) && $sox_id != '-1') {
            $o_delivery = ox_new(\Oxid_Esales\Eshop\Application\Model\Delivery::class);
            $o_delivery->load($sox_id);
            //Disable editing for derived articles
            if ($o_delivery->is_derived()) {
                $this->_a_view_data['readonly'] = true;
            }
        }
        $this->_a_view_data['allgroups2'] = $o_groups;
        $i_aoc = Registry::get_request()->get_request_escaped_parameter('aoc');
        if ($i_aoc == 1) {
            $o_delivery_users_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Users_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_delivery_users_ajax->get_columns();
            return 'popups/delivery_users';
        }
        if ($i_aoc == 2) {
            $o_delivery_groups_ajax = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Delivery_Groups_Ajax::class);
            $this->_a_view_data['oxajax'] = $o_delivery_groups_ajax->get_columns();
            return 'popups/delivery_groups';
        }
        return 'delivery_users';
    }
}