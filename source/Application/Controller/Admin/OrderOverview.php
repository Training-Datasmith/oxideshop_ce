<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Application\Model\Order;
use Oxid_Esales\Eshop\Core\Price;
use Oxid_Esales\Eshop\Core\Registry;
/**
 * Admin order overview manager.
 * Collects order overview information, updates it on user submit, etc.
 * Admin Menu: Orders -> Display Orders -> Overview.
 */
class Order_Overview extends \Oxid_Esales\Eshop\Application\Controller\Admin\Admin_Details_Controller
{
    /** @inheritdoc */
    public function render()
    {
        $my_config = Registry::get_config();
        parent::render();
        $o_order = ox_new(Order::class);
        $o_cur = $my_config->get_act_shop_currency_object();
        $o_lang = Registry::get_lang();
        $sox_id = $this->get_edit_object_id();
        if (isset($sox_id) && $sox_id != '-1') {
            $o_order->load($sox_id);
            $this->_a_view_data['edit'] = $o_order;
            $this->_a_view_data['aProductVats'] = $o_order->get_product_vats();
            $this->_a_view_data['orderArticles'] = $o_order->get_order_articles();
            $this->_a_view_data['giftCard'] = $o_order->get_gift_card();
            $this->_a_view_data['paymentType'] = $this->get_payment_type($o_order);
            $this->_a_view_data['deliveryType'] = $o_order->get_del_set();
            if ($o_order->get_field_data('oxtsprotectcosts')) {
                $this->_a_view_data['tsprotectcosts'] = $o_lang->format_currency($o_order->get_field_data('oxtsprotectcosts'), $o_cur);
            }
        }
        $today_sum = Price::get_price_in_act_currency($o_order->get_order_sum(true));
        $this->_a_view_data['ordersum'] = $o_lang->format_currency($today_sum, $o_cur);
        $this->_a_view_data['ordercnt'] = $o_order->get_order_cnt(true);
        $total_sum = Price::get_price_in_act_currency($o_order->get_order_sum());
        $this->_a_view_data['ordertotalsum'] = $o_lang->format_currency($total_sum, $o_cur);
        $this->_a_view_data['ordertotalcnt'] = $o_order->get_order_cnt();
        $this->_a_view_data['afolder'] = $my_config->get_config_param('aOrderfolder');
        $this->_a_view_data['alangs'] = $o_lang->get_language_names();
        $this->_a_view_data['currency'] = $o_cur;
        return 'order_overview';
    }
    /**
     * Returns user payment used for current order. In case current order was executed using
     * just for preview user payment is set from oxPayment
     *
     * @param object $oOrder Order object
     *
     * @return \OxidEsales\Eshop\Application\Model\Payment
     */
    protected function get_payment_type($o_order)
    {
        if (!($o_user_payment = $o_order->get_payment_type()) && $o_order->oxorder__oxpaymenttype->value) {
            $o_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\Payment::class);
            if ($o_payment->load($o_order->oxorder__oxpaymenttype->value)) {
                // in case due to security reasons payment info was not kept in db
                $o_user_payment = ox_new(\Oxid_Esales\Eshop\Application\Model\User_Payment::class);
                $o_user_payment->oxpayments__oxdesc = new \Oxid_Esales\Eshop\Core\Field($o_payment->oxpayments__oxdesc->value);
            }
        }
        return $o_user_payment;
    }
    /**
     * Gets proper file name
     *
     * @param string $sFilename file name
     *
     * @return string
     */
    public function make_valid_file_name($s_filename)
    {
        $s_filename = preg_replace('/[\s]+/', '_', $s_filename);
        $s_filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '', (string) $s_filename);
        return str_replace(' ', '_', $s_filename);
    }
    /**
     * Sends order.
     */
    public function sendorder(): void
    {
        $o_order = ox_new(Order::class);
        if ($o_order->load($this->get_edit_object_id())) {
            $o_order->oxorder__oxsenddate = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d H:i:s', Registry::get_utils_date()->get_time()));
            $o_order->save();
            // #1071C
            $o_order_articles = $o_order->get_order_articles();
            foreach ($o_order_articles as $s_oxid => $o_article) {
                // remove canceled articles from list
                if ($o_article->oxorderarticles__oxstorno->value == 1) {
                    $o_order_articles->offsetUnset($s_oxid);
                }
            }
            if ($bl_mail = Registry::get_request()->get_request_escaped_parameter('sendmail')) {
                // send eMail
                $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
                $o_email->send_sended_now_mail($o_order);
            }
        }
    }
    /**
     * Resets order shipping date.
     */
    public function resetorder(): void
    {
        $o_order = ox_new(Order::class);
        if ($o_order->load($this->get_edit_object_id())) {
            $o_order->oxorder__oxsenddate = new \Oxid_Esales\Eshop\Core\Field('0000-00-00 00:00:00');
            $o_order->save();
        }
    }
    /**
     * Get information about shipping status
     *
     * @return bool
     */
    public function can_reset_shipping_date()
    {
        $o_order = ox_new(Order::class);
        if ($o_order->load($this->get_edit_object_id())) {
            return $o_order->oxorder__oxstorno->value == '0' && !($o_order->oxorder__oxsenddate->value == '0000-00-00 00:00:00' || $o_order->oxorder__oxsenddate->value == '-');
        }
        return false;
    }
}