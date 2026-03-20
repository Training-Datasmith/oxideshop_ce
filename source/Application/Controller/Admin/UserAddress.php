<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin user address setting manager.
 * Collects user address settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Addresses.
 */
class User_Address extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * If true, means that address was deleted
     *
     * @var bool
     */
    protected $_bl_delete = false;
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
            $o_user->load($sox_id);
            // load adress
            $s_address_id_parameter = Registry::get_request()->get_request_escaped_parameter('oxaddressid');
            $sox_address_id = $this->s_saved_oxid ?? $s_address_id_parameter;
            if ($sox_address_id != '-1' && isset($sox_address_id)) {
                $o_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
                $o_adress->load($sox_address_id);
                $this->_a_view_data['edit'] = $o_adress;
            }
            $this->_a_view_data['oxaddressid'] = $sox_address_id;
            // generate selected
            $o_address_list = $o_user->get_user_addresses();
            foreach ($o_address_list as $o_address) {
                if ($o_address->oxaddress__oxid->value == $sox_address_id) {
                    $o_address->selected = 1;
                    break;
                }
            }
            $this->_a_view_data['edituser'] = $o_user;
        }
        $o_country_list = ox_new(\Oxid_Esales\Eshop\Application\Model\Country_List::class);
        $o_country_list->load_active_countries(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_object_tpl_language());
        $this->_a_view_data['countrylist'] = $o_country_list;
        if (!$this->allow_admin_edit($sox_id)) {
            $this->_a_view_data['readonly'] = true;
        }
        return 'user_address';
    }
    /**
     * Saves user addressing information.
     */
    public function save(): void
    {
        parent::save();
        if ($this->allow_admin_edit($this->get_edit_object_id())) {
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            $o_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
            if (isset($a_params['oxaddress__oxid']) && $a_params['oxaddress__oxid'] == '-1') {
                $a_params['oxaddress__oxid'] = null;
            } else {
                $o_adress->load($a_params['oxaddress__oxid']);
            }
            $o_adress->assign($a_params);
            $o_adress->save();
            $this->s_saved_oxid = $o_adress->get_id();
        }
    }
    /**
     * Deletes user addressing information.
     */
    public function del_address(): void
    {
        $this->_bl_delete = false;
        if ($this->allow_admin_edit($this->get_edit_object_id())) {
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            if (isset($a_params['oxaddress__oxid']) && $a_params['oxaddress__oxid'] != '-1') {
                $o_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\Address::class);
                $this->_bl_delete = $o_adress->delete($a_params['oxaddress__oxid']);
            }
        }
    }
}