<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin order address manager.
 * Collects order addressing information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Address.
 */
class Order_Address extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            $o_order->load($sox_id);
            $this->_a_view_data['edit'] = $o_order;
        }
        $o_country_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Country_List::class);
        $o_country_list->load_active_countries(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_object_tpl_language());
        $this->_a_view_data['countrylist'] = $o_country_list;
        return 'order_address';
    }
    /**
     * Iterates through data array, checks if specified fields are filled
     * in, cleanups not needed data
     *
     * @param array  $aData          data to process
     * @param string $sTypeToProcess data type to process e.g. "oxorder__oxdel"
     * @param array  $aIgnore        fields which must be ignored while processing
     */
    protected function process_address($a_data, $s_type_to_process, $a_ignore)
    {
        // empty address fields?
        $bl_empty = true;
        // here we will store names of fields which needs to be cleaned up
        $a_fields = [];
        foreach ($a_data as $s_name => $s_value) {
            // if field type matches..
            if (str_contains((string) $s_name, $s_type_to_process)) {
                // storing which fields must be unset..
                $a_fields[] = $s_name;
                // ignoring whats need to be ignored and testing values
                if (!in_array($s_name, $a_ignore) && $s_value) {
                    // something was found - means leaving as is..
                    $bl_empty = false;
                    break;
                }
            }
        }
        // cleanup if empty
        if ($bl_empty) {
            foreach ($a_fields as $s_name) {
                $a_data[$s_name] = '';
            }
        }
        return $a_data;
    }
    /**
     * Saves ordering address information.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = (array) Registry::get_request()->get_request_escaped_parameter('editval');
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($sox_id != '-1') {
            $o_order->load($sox_id);
        } else {
            $a_params['oxorder__oxid'] = null;
        }
        $a_params = $this->process_address($a_params, 'oxorder__oxdel', ['oxorder__oxdelsal']);
        $o_order->assign($a_params);
        $o_order->save();
        // set oxid if inserted
        $this->set_edit_object_id($o_order->get_id());
    }
}