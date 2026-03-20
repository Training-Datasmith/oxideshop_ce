<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin order remark manager.
 * Collects order remark information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> History.
 */
class Order_Remark extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        $s_remox_id = Registry::get_request()->get_request_escaped_parameter('rem_oxid');
        if (isset($sox_id) && $sox_id != '-1') {
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            $o_order->load($sox_id);
            // all remark
            $o_rems = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $o_rems->init('oxremark');
            $s_user_id_field = 'oxorder__oxuserid';
            $s_select = 'select * from oxremark where oxparentid = :oxparentid order by oxcreate desc';
            $o_rems->select_string($s_select, ['oxparentid' => $o_order->{$s_user_id_field}->value]);
            foreach ($o_rems as $key => $val) {
                if ($val->oxremark__oxid->value == $s_remox_id) {
                    $val->selected = 1;
                    $o_rems[$key] = $val;
                    break;
                }
            }
            $this->_a_view_data['allremark'] = $o_rems;
            if (isset($s_remox_id)) {
                $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
                $o_remark->load($s_remox_id);
                $this->_a_view_data['remarktext'] = $o_remark->oxremark__oxtext->value;
                $this->_a_view_data['remarkheader'] = $o_remark->oxremark__oxheader->value;
            }
        }
        return 'order_remark';
    }
    /**
     * Saves order history item text changes.
     */
    public function save(): void
    {
        parent::save();
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($o_order->load($this->get_edit_object_id())) {
            $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
            $o_remark->load(Registry::get_request()->get_request_escaped_parameter('rem_oxid'));
            $o_remark->oxremark__oxtext = new \Oxid_Esales\Eshop\Core\Field(Registry::get_request()->get_request_escaped_parameter('remarktext'));
            $o_remark->oxremark__oxheader = new \Oxid_Esales\Eshop\Core\Field(Registry::get_request()->get_request_escaped_parameter('remarkheader'));
            $o_remark->oxremark__oxtype = new \Oxid_Esales\Eshop\Core\Field('r');
            $o_remark->oxremark__oxparentid = new \Oxid_Esales\Eshop\Core\Field($o_order->oxorder__oxuserid->value);
            $o_remark->save();
        }
    }
    /**
     * Deletes order history item.
     */
    public function delete(): void
    {
        $o_remark = ox_new(\Oxid_Esales\Eshop\Application\Model\Remark::class);
        $o_remark->delete(Registry::get_request()->get_request_escaped_parameter('rem_oxid'));
    }
}