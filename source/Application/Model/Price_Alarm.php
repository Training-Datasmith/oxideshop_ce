<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

/**
 * PriceAlarm manager.
 * Performs PriceAlarm data/objects loading, deleting.
 */
class Price_Alarm extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    /**
     * Current class name
     *
     * @var string
     */
    protected $_s_class_name = 'oxpricealarm';
    /**
     * Article object
     *
     * @var object
     */
    protected $_o_article;
    /**
     * Formatted original article price
     *
     * @var string
     */
    protected $_f_price;
    /**
     * Original article price
     *
     * @var double
     */
    protected $_d_price;
    /**
     * Full article title
     *
     * @var string
     */
    protected $_s_title;
    /**
     * Currency object
     *
     * @var object
     */
    protected $_o_currency;
    /**
     * Customer proposed price
     *
     * @var string
     */
    protected $_f_proposed_price;
    /**
     * PriceAlarm status
     *
     * @var int
     */
    protected $_i_status;
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()), loads
     * base shop objects.
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxpricealarm');
    }
    /**
     * Inserts object data into DB, returns true on success.
     *
     * @return bool
     */
    protected function insert()
    {
        // set oxinsert value
        $this->oxpricealarm__oxinsert = new \Oxid_Esales\Eshop\Core\Field(date('Y-m-d', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()));
        return parent::insert();
    }
    /**
     * Loads pricealarm article
     *
     * @return object
     */
    public function get_article()
    {
        if ($this->_o_article == null) {
            $this->_o_article = false;
            $o_article = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
            if ($o_article->load($this->oxpricealarm__oxartid->value)) {
                $this->_o_article = $o_article;
            }
        }
        return $this->_o_article;
    }
    /**
     * Returns formatted pricealarm article original price
     *
     * @return string
     */
    public function get_f_price()
    {
        if ($this->_f_price == null) {
            $this->_f_price = false;
            if ($d_art_price = $this->get_price()) {
                $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
                $o_this_curr = $this->get_price_alarm_currency();
                $this->_f_price = $my_lang->format_currency($d_art_price, $o_this_curr);
            }
        }
        return $this->_f_price;
    }
    /**
     * Returns pricealarm article original price
     *
     * @return double
     */
    public function get_price()
    {
        if ($this->_d_price == null) {
            $this->_d_price = false;
            if ($o_article = $this->get_article()) {
                $my_utils = \Oxid_Esales\Eshop\Core\Registry::get_utils();
                $o_this_curr = $this->get_price_alarm_currency();
                // #889C - Netto prices in Admin
                // (we have to call $oArticle->getPrice() to get price with VAT)
                $d_art_price = $o_article->get_price()->get_brutto_price() * $o_this_curr->rate;
                $d_art_price = $my_utils->f_round($d_art_price);
                $this->_d_price = $d_art_price;
            }
        }
        return $this->_d_price;
    }
    /**
     * Returns pricealarm article full title
     *
     * @return string
     */
    public function get_title()
    {
        if ($this->_s_title == null) {
            $this->_s_title = false;
            if ($o_article = $this->get_article()) {
                $this->_s_title = $o_article->oxarticles__oxtitle->value;
                if ($o_article->oxarticles__oxparentid->value && !$o_article->oxarticles__oxtitle->value) {
                    $o_parent = ox_new(\Oxid_Esales\Eshop\Application\Model\Article::class);
                    $o_parent->load($o_article->oxarticles__oxparentid->value);
                    $this->_s_title = $o_parent->oxarticles__oxtitle->value . ' ' . $o_article->oxarticles__oxvarselect->value;
                }
            }
        }
        return $this->_s_title;
    }
    /**
     * Returns pricealarm currency object
     *
     * @return object
     */
    public function get_price_alarm_currency()
    {
        if ($this->_o_currency == null) {
            $this->_o_currency = false;
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            $o_this_curr = $my_config->get_currency_object($this->oxpricealarm__oxcurrency->value);
            // #869A we should perform currency conversion
            // (older versions doesn't have currency info - assume as it is default - first in currency array)
            if (!$o_this_curr) {
                $o_def_curr = $my_config->get_act_shop_currency_object();
                $o_this_curr = $my_config->get_currency_object($o_def_curr->name);
                $this->oxpricealarm__oxcurrency->set_value($o_def_curr->name);
            }
            $this->_o_currency = $o_this_curr;
        }
        return $this->_o_currency;
    }
    /**
     * Returns formatted proposed price
     *
     * @return string
     */
    public function get_f_proposed_price()
    {
        if ($this->_f_proposed_price == null) {
            $this->_f_proposed_price = false;
            if ($o_this_curr = $this->get_price_alarm_currency()) {
                $my_lang = \Oxid_Esales\Eshop\Core\Registry::get_lang();
                $this->_f_proposed_price = $my_lang->format_currency($this->oxpricealarm__oxprice->value, $o_this_curr);
            }
        }
        return $this->_f_proposed_price;
    }
    /**
     * Returns pricealarm status
     *
     * @return integer
     */
    public function get_price_alarm_status()
    {
        if ($this->_i_status == null) {
            // neutral status
            $this->_i_status = 0;
            // shop price is less or equal
            $d_art_price = $this->get_price();
            if ($this->oxpricealarm__oxprice->value >= $d_art_price) {
                $this->_i_status = 1;
            }
            // suggestion to user is sent
            if ($this->oxpricealarm__oxsended->value != '0000-00-00 00:00:00') {
                $this->_i_status = 2;
            }
        }
        return $this->_i_status;
    }
}