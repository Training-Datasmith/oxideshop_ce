<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin user payment settings manager.
 * Collects user payment settings, updates it on user submit, etc.
 * Admin Menu: User Administration -> Users -> Payment.
 */
class User_Payment extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * (default false).
     *
     * @var bool
     */
    protected $_bl_delete = false;
    /**
     * Selected user
     *
     * @var object
     */
    protected $_o_active_user;
    /**
     * Selected user payment
     *
     * @var string
     */
    protected $_s_payment_id;
    /**
     * List of all payments
     *
     * @var object
     */
    protected $_o_payment_types;
    /**
     * Selected user payment
     *
     * @var object
     */
    protected $_o_user_payment;
    /**
     * List of all user payments
     *
     * @var object
     */
    protected $_o_user_payments;
    /**
     * Executes parent method parent::render(), creates oxlist and oxuser objects
     * and returns the name of the template file.
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $this->_a_view_data['edit'] = $this->get_sel_user_payment();
        $this->_a_view_data['oxpaymentid'] = $this->get_payment_id();
        $this->_a_view_data['paymenttypes'] = $this->get_payment_types();
        $this->_a_view_data['edituser'] = $this->get_user();
        $this->_a_view_data['userpayments'] = $this->get_user_payments();
        $s_ox_id = $this->get_edit_object_id();
        if (!$this->allow_admin_edit($s_ox_id)) {
            $this->_a_view_data['readonly'] = true;
        }
        return 'user_payment';
    }
    /**
     * Saves user payment settings.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        if ($this->allow_admin_edit($sox_id)) {
            $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
            $a_dynvalues = Registry::get_request()->get_request_escaped_parameter('dynvalue');
            if (isset($a_dynvalues)) {
                // store the dynvalues
                $a_params['oxuserpayments__oxvalue'] = \Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_to_text($a_dynvalues);
            }
            if ($a_params['oxuserpayments__oxid'] == '-1') {
                $a_params['oxuserpayments__oxid'] = null;
            }
            $o_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
            $o_adress->assign($a_params);
            $o_adress->save();
        }
    }
    /**
     * Deletes selected user payment information.
     */
    public function del_payment(): void
    {
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $sox_id = $this->get_edit_object_id();
        if ($this->allow_admin_edit($sox_id)) {
            if ($a_params['oxuserpayments__oxid'] != '-1') {
                $o_adress = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
                if ($o_adress->load($a_params['oxuserpayments__oxid'])) {
                    $this->_bl_delete = (bool) $o_adress->delete();
                }
            }
        }
    }
    /**
     * Returns selected user
     *
     * @return \OxidEsales\Eshop\Application\Model\User|false
     */
    public function get_user()
    {
        if ($this->_o_active_user == null) {
            $this->_o_active_user = false;
            $s_ox_id = $this->get_edit_object_id();
            if (isset($s_ox_id) && $s_ox_id != '-1') {
                // load object
                $this->_o_active_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                $this->_o_active_user->load($s_ox_id);
            }
        }
        return $this->_o_active_user;
    }
    /**
     * Returns selected Payment Id
     *
     * @return object
     */
    public function get_payment_id()
    {
        if ($this->_s_payment_id == null) {
            $this->_s_payment_id = Registry::get_request()->get_request_escaped_parameter('oxpaymentid');
            if (!$this->_s_payment_id || $this->_bl_delete) {
                if ($o_user = $this->get_user()) {
                    $o_user_payments = $o_user->get_user_payments();
                    if (isset($o_user_payments[0])) {
                        $this->_s_payment_id = $o_user_payments[0]->oxuserpayments__oxid->value;
                    }
                }
            }
            if (!$this->_s_payment_id) {
                $this->_s_payment_id = '-1';
            }
        }
        return $this->_s_payment_id;
    }
    /**
     * Returns selected Payment Id
     *
     * @return object
     */
    public function get_payment_types()
    {
        if ($this->_o_payment_types == null) {
            // all paymenttypes
            $this->_o_payment_types = ox_new(\Oxid_Esales\Eshop\Core\Model\List_Model::class);
            $this->_o_payment_types->init('oxpayment');
            $o_list_object = $this->_o_payment_types->get_base_object();
            $o_list_object->set_language(\Oxid_Esales\Eshop\Core\Registry::get_lang()->get_object_tpl_language());
            $this->_o_payment_types->get_list();
        }
        return $this->_o_payment_types;
    }
    /**
     * Returns selected Payment
     *
     * @return object
     */
    public function get_sel_user_payment()
    {
        if ($this->_o_user_payment == null) {
            $this->_o_user_payment = false;
            $s_payment_id = $this->get_payment_id();
            if ($s_payment_id != '-1' && isset($s_payment_id)) {
                $this->_o_user_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
                $this->_o_user_payment->load($s_payment_id);
                $s_template = $this->_o_user_payment->oxuserpayments__oxvalue->value;
                // generate selected paymenttype
                $o_payment_types = $this->get_payment_types();
                foreach ($o_payment_types as $o_payment) {
                    if ($o_payment->oxpayments__oxid->value == $this->_o_user_payment->oxuserpayments__oxpaymentsid->value) {
                        $o_payment->selected = 1;
                        // if there are no values assigned we set default from paymenttype
                        if (!$s_template) {
                            $s_template = $o_payment->oxpayments__oxvaldesc->value;
                        }
                        break;
                    }
                }
                $this->_o_user_payment->set_dyn_values(\Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_from_text($s_template));
            }
        }
        return $this->_o_user_payment;
    }
    /**
     * Returns selected Payment Id
     *
     * @return object
     */
    public function get_user_payments()
    {
        if ($this->_o_user_payments == null) {
            $this->_o_user_payments = false;
            if ($o_user = $this->get_user()) {
                $s_tpl_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang()->get_object_tpl_language();
                $s_payment_id = $this->get_payment_id();
                $this->_o_user_payments = $o_user->get_user_payments();
                // generate selected
                foreach ($this->_o_user_payments as $o_user_payment) {
                    $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
                    $o_payment->set_language($s_tpl_lang);
                    $o_payment->load($o_user_payment->oxuserpayments__oxpaymentsid->value);
                    $o_user_payment->oxpayments__oxdesc = clone $o_payment->oxpayments__oxdesc;
                    if ($o_user_payment->oxuserpayments__oxid->value == $s_payment_id) {
                        $o_user_payment->selected = 1;
                        break;
                    }
                }
            }
        }
        return $this->_o_user_payments;
    }
}