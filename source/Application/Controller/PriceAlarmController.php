<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller;

use Oxid_Esales\Eshop\Core\Field;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Utility\Email\Email_Validator_Service_Bridge_Interface;
/**
 * PriceAlarm window.
 * Arranges "pricealarm" window, by sending eMail and storing into Database (etc.)
 * submission. Result - "pricealarm"  template. After user correctly
 * fulfils all required fields all information is sent to shop owner by
 * email.
 * OXID eShop -> pricealarm.
 */
class Price_Alarm_Controller extends \Oxid_Esales\Eshop\Application\Controller\Frontend_Controller
{
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template = 'pricealarm';
    /**
     * Current article.
     *
     * @var object
     */
    protected $_o_article;
    /**
     * Bid price.
     *
     * @var string
     */
    protected $_s_bid_price;
    /**
     * Price alarm status.
     *
     * @var integer
     */
    protected $_i_price_alarm_status;
    /**
     * Validates email
     * address. If email is wrong - returns false and exits. If email
     * address is OK - creates prcealarm object and saves it
     * (oxpricealarm::save()). Sends pricealarm notification mail
     * to shop owner.
     *
     * @return  bool    false on error
     */
    public function addme(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
        $email_validator = Container_Facade::get(Email_Validator_Service_Bridge_Interface::class);
        $a_params = Registry::get_request()->get_request_escaped_parameter('pa');
        if (!isset($a_params['email']) || !$email_validator->is_email_valid($a_params['email'])) {
            $this->_i_price_alarm_status = 0;
            return;
        }
        $o_cur = $my_config->get_act_shop_currency_object();
        // convert currency to default
        $d_price = $my_utils->currency2Float($a_params['price']);
        $o_alarm = ox_new(\Oxid_Esales\Eshop\Application\Model\Price_Alarm::class);
        $o_alarm->oxpricealarm__oxuserid = new Field(Registry::get_session()->get_variable('usr'));
        $o_alarm->oxpricealarm__oxemail = new Field($a_params['email']);
        $o_alarm->oxpricealarm__oxartid = new Field($a_params['aid']);
        $o_alarm->oxpricealarm__oxprice = new Field($my_utils->f_round($d_price, $o_cur));
        $o_alarm->oxpricealarm__oxshopid = new Field($my_config->get_shop_id());
        $o_alarm->oxpricealarm__oxcurrency = new Field($o_cur->name);
        $o_alarm->oxpricealarm__oxlang = new Field(Registry::get_lang()->get_base_language());
        $o_alarm->save();
        // Send Email
        $o_email = ox_new(\Oxid_Esales\Eshop\Core\Email::class);
        $this->_i_price_alarm_status = (int) $o_email->send_pricealarm_notification($a_params, $o_alarm);
    }
    /**
     * Template variable getter. Returns bid price
     *
     * @return string
     */
    public function get_bid_price()
    {
        if ($this->_s_bid_price === null) {
            $this->_s_bid_price = false;
            $a_params = $this->get_params();
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $i_price = \Oxid_Esales\Eshop\Core\Registry::get_utils()->currency2Float($a_params['price']);
            $this->_s_bid_price = \Oxid_Esales\Eshop\Core\Registry::get_lang()->format_currency($i_price, $o_cur);
        }
        return $this->_s_bid_price;
    }
    /**
     * Template variable getter. Returns active article
     *
     * @return object
     */
    public function get_product()
    {
        if ($this->_o_article === null) {
            $this->_o_article = false;
            $a_params = $this->get_params();
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            $o_article->load($a_params['aid']);
            $this->_o_article = $o_article;
        }
        return $this->_o_article;
    }
    /**
     * Returns params (article id, bid price)
     *
     * @return array
     */
    private function get_params()
    {
        return Registry::get_request()->get_request_escaped_parameter('pa');
    }
    /**
     * Return pricealarm status (if it was send)
     *
     * @return integer
     */
    public function get_price_alarm_status()
    {
        return $this->_i_price_alarm_status;
    }
}