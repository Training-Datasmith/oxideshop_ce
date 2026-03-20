<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin article main order manager.
 * Performs collection and updatind (on user submit) main item information.
 * Admin Menu: Orders -> Display Orders -> Main.
 */
class Order_Main extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /**
     * Whitelist of parameters whose change does not require a full order recalculation.
     *
     * @var array
     */
    protected $fields_trigger_no_order_recalculation = ['oxorder__oxordernr', 'oxorder__oxbillnr', 'oxorder__oxtrackcode', 'oxorder__oxpaid'];
    /** @inheritdoc */
    public function render()
    {
        parent::render();
        $sox_id = $this->_a_view_data['oxid'] = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            // load object
            $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
            $o_order->load($sox_id);
            // paid ?
            $s_ox_paid_field = 'oxorder__oxpaid';
            $s_del_type_field = 'oxorder__oxdeltype';
            if ($o_order->{$s_ox_paid_field}->value != '0000-00-00 00:00:00') {
                $o_order->bl_is_paid = true;
                /** @var \OxidEsales\Eshop\Core\UtilsDate $oUtilsDate */
                $o_utils_date = \Oxid_Esales\Eshop\Core\Registry::get_utils_date();
                $o_order->{$s_ox_paid_field} = new \Oxid_Esales\Eshop\Core\Field($o_utils_date->format_db_date($o_order->{$s_ox_paid_field}->value));
            }
            $this->_a_view_data['edit'] = $o_order;
            $this->_a_view_data['paymentType'] = $o_order->get_payment_type();
            $this->_a_view_data['oShipSet'] = $o_order->get_shipping_set_list();
            if ($o_order->{$s_del_type_field}->value) {
                // order user
                $o_user = ox_new(\Oxid_Esales\Eshop\Application\Model\User::class);
                $o_user->load($o_order->oxorder__oxuserid->value);
                // order sum in default currency
                $d_price = $o_order->oxorder__oxtotalbrutsum->value / $o_order->oxorder__oxcurrate->value;
                /** @var \OxidEsales\Eshop\Application\Model\PaymentList $oPaymentList */
                $o_payment_list = \Oxid_Esales\Eshop\Core\Registry::get(\Oxid_Esales\Eshop\Application\Model\Payment_List::class);
                $this->_a_view_data['oPayments'] = $o_payment_list->get_payment_list($o_order->{$s_del_type_field}->value, $d_price, $o_user);
            }
            // any voucher used ?
            $this->_a_view_data['aVouchers'] = $o_order->get_voucher_nr_list();
        }
        $this->_a_view_data['sNowValue'] = date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time());
        return 'order_main';
    }
    /**
     * Saves main orders configuration parameters.
     */
    public function save(): void
    {
        parent::save();
        $sox_id = $this->get_edit_object_id();
        $a_params = Registry::get_request()->get_request_escaped_parameter('editval');
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($sox_id != '-1') {
            $o_order->load($sox_id);
        } else {
            $a_params['oxorder__oxid'] = null;
        }
        $need_order_recalculate = false;
        if (is_array($a_params)) {
            foreach ($a_params as $parameter => $value) {
                //parameter changes for not whitelisted parameters trigger order recalculation
                $order_field = $o_order->{$parameter};
                if ($value != $order_field->value && !in_array($parameter, $this->fields_trigger_no_order_recalculation)) {
                    $need_order_recalculate = true;
                    continue;
                }
            }
        }
        //change payment
        $s_pay_id = Registry::get_request()->get_request_escaped_parameter('setPayment');
        if (!empty($s_pay_id) && $s_pay_id != $o_order->oxorder__oxpaymenttype->value) {
            $a_params['oxorder__oxpaymenttype'] = $s_pay_id;
            $need_order_recalculate = true;
        }
        $o_order->assign($a_params);
        $a_dynvalues = Registry::get_request()->get_request_escaped_parameter('dynvalue');
        if (isset($a_dynvalues)) {
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
            $o_payment->load($o_order->oxorder__oxpaymentid->value);
            $o_payment->oxuserpayments__oxvalue->set_value(\Oxid_Esales\Eshop\Core\Registry::get_utils()->assign_values_to_text($a_dynvalues));
            $o_payment->save();
            $need_order_recalculate = true;
        }
        //change delivery set
        $s_del_set_id = Registry::get_request()->get_request_escaped_parameter('setDelSet');
        if (!empty($s_del_set_id) && $s_del_set_id != $o_order->oxorder__oxdeltype->value) {
            $o_order->oxorder__oxpaymenttype->set_value('oxempty');
            $o_order->set_delivery($s_del_set_id);
            $need_order_recalculate = true;
        } else {
            // keeps old delivery cost
            $o_order->reload_delivery(false);
        }
        if ($need_order_recalculate) {
            // keeps old discount
            $o_order->reload_discount(false);
            $o_order->recalculate_order();
        } else {
            //nothing changed in order that requires a full recalculation
            $o_order->save();
        }
        // set oxid if inserted
        $this->set_edit_object_id($o_order->get_id());
    }
    /**
     * Sends order.
     */
    public function send_order(): void
    {
        $sox_id = $this->get_edit_object_id();
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($o_order->load($sox_id)) {
            // #632A
            $o_order->oxorder__oxsenddate = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()));
            $o_order->save();
            // #1071C
            $o_order->get_order_articles(true);
            if (Registry::get_request()->get_request_escaped_parameter('sendmail')) {
                // send eMail
                $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
                $o_email->send_sended_now_mail($o_order);
            }
            $this->on_order_send();
        }
    }
    /**
     * Sends download links.
     */
    public function send_download_links(): void
    {
        $sox_id = $this->get_edit_object_id();
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($o_order->load($sox_id)) {
            $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
            $o_email->send_download_links_mail($o_order);
        }
    }
    /**
     * Resets order shipping date.
     */
    public function reset_order(): void
    {
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        if ($o_order->load($this->get_edit_object_id())) {
            $o_order->oxorder__oxsenddate = new \Oxid_Esales\Eshop\Core\Field('0000-00-00 00:00:00');
            $o_order->save();
            $this->on_order_reset();
        }
    }
    /**
     * Method is used for overriding.
     */
    protected function on_order_send()
    {
    }
    /**
     * Method is used for overriding.
     */
    protected function on_order_reset()
    {
    }
}