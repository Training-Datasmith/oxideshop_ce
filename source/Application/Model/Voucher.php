<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Model;

use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use stdClass;
/**
 * Voucher manager.
 * Performs deletion, generating, assigning to group and other voucher
 * managing functions.
 */
class Voucher extends \Oxid_Esales\Eshop\Core\Model\Base_Model
{
    protected $_o_serie;
    /**
     * Vouchers does not need shop id check as this causes problems with
     * inherited vouchers. Voucher validity check is made by oxVoucher::getVoucherByNr()
     *
     * @var bool
     */
    protected $_bl_disable_shop_check = true;
    /**
     * @var string Name of current class
     */
    protected $_s_class_name = 'oxvoucher';
    /**
     * Class constructor, initiates parent constructor (parent::oxBase()).
     */
    public function __construct()
    {
        parent::__construct();
        $this->init('oxvouchers');
    }
    /**
     * Gets voucher from db by given number.
     *
     * @param string $sVoucherNr         Voucher number
     * @param array  $aVouchers          Array of available vouchers (default array())
     * @param bool   $blCheckavalability check if voucher is still reserver od not
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return mixed
     */
    public function get_voucher_by_nr($s_voucher_nr, $a_vouchers = [], $bl_checkavalability = false)
    {
        $o_ret = null;
        if (!empty($s_voucher_nr)) {
            $s_view_name = $this->get_view_name();
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_series_view_name = $table_view_name_generator->get_view_name('oxvoucherseries');
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            $s_q = "select {$s_view_name}.* from {$s_view_name}, {$s_series_view_name} where\n                        {$s_series_view_name}.oxid = {$s_view_name}.oxvoucherserieid and\n                        {$s_view_name}.oxvouchernr = " . $o_db->quote($s_voucher_nr) . ' and ';
            if (is_array($a_vouchers)) {
                foreach ($a_vouchers as $s_voucher_id => $s_skip_voucher_nr) {
                    $s_q .= "{$s_view_name}.oxid != " . $o_db->quote($s_voucher_id) . ' and ';
                }
            }
            $s_q .= "( {$s_view_name}.oxorderid is NULL || {$s_view_name}.oxorderid = '' ) ";
            $s_q .= " and ( {$s_view_name}.oxdateused is NULL || {$s_view_name}.oxdateused = 0 ) ";
            //voucher timeout for 3 hours
            if ($bl_checkavalability) {
                $i_time = time() - $this->get_voucher_timeout();
                $s_q .= " and {$s_view_name}.oxreserved < '{$i_time}' order by {$s_view_name}.oxreserved asc ";
            }
            $s_q .= ' limit 1 FOR UPDATE';
            if (!$o_ret = $this->assign_record($s_q)) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
                $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
                $o_ex->set_voucher_nr($s_voucher_nr);
                throw $o_ex;
            }
        }
        return $o_ret;
    }
    /**
     * marks voucher as used
     *
     * @param string $sOrderId  order id
     * @param string $sUserId   user id
     * @param double $dDiscount used discount
     */
    public function mark_as_used($s_order_id, $s_user_id, $d_discount): void
    {
        //saving oxreserved field
        if ($this->oxvouchers__oxid->value) {
            $this->oxvouchers__oxorderid->set_value($s_order_id);
            $this->oxvouchers__oxuserid->set_value($s_user_id);
            $this->oxvouchers__oxdiscount->set_value($d_discount);
            $this->oxvouchers__oxdateused->set_value(date('Y-m-d', \Oxid_Esales\Eshop\Core\Registry::get_utils_date()->get_time()));
            $this->save();
        }
    }
    /**
     * mark voucher as reserved
     */
    public function mark_as_reserved(): void
    {
        //saving oxreserved field
        $s_voucher_id = $this->oxvouchers__oxid->value;
        if ($s_voucher_id) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_master();
            $s_q = 'update oxvouchers set oxreserved = :oxreserved where oxid = :oxid';
            $o_db->execute($s_q, ['oxreserved' => time(), 'oxid' => $s_voucher_id]);
        }
    }
    /**
     * un mark as reserved
     */
    public function un_mark_as_reserved(): void
    {
        //saving oxreserved field
        $s_voucher_id = $this->oxvouchers__oxid->value;
        if ($s_voucher_id) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_q = 'update oxvouchers set oxreserved = 0 where oxid = :oxid';
            $o_db->execute($s_q, ['oxid' => $s_voucher_id]);
        }
    }
    /**
     * Returns the discount value used.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return float
     */
    public function get_discount_value($d_price)
    {
        if ($this->is_product_voucher()) {
            return $this->get_product_discount_value((float) $d_price);
        }
        if ($this->is_category_voucher()) {
            return $this->get_category_discount_value((float) $d_price);
        }
        return $this->get_generic_discount_value((float) $d_price);
    }
    // Checking General Availability
    /**
     * Checks availability without user logged in. Returns array with errors.
     *
     * @param array  $aVouchers array of vouchers
     * @param double $dPrice    current sum (price)
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return array
     */
    public function check_voucher_availability($a_vouchers, $d_price)
    {
        $this->is_available_with_same_series($a_vouchers);
        $this->is_available_with_other_series($a_vouchers);
        $this->is_valid_date();
        $this->is_available_price($d_price);
        $this->is_not_reserved();
        $this->is_available();
        // returning true - no exception was thrown
        return true;
    }
    /**
     * Performs basket level voucher availability check (no need to check if voucher
     * is reserved or so).
     *
     * @param array  $aVouchers array of vouchers
     * @param double $dPrice    current sum (price)
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return array
     */
    public function check_basket_voucher_availability($a_vouchers, $d_price)
    {
        $this->is_available_with_same_series($a_vouchers);
        $this->is_available_with_other_series($a_vouchers);
        $this->is_valid_date();
        $this->is_available_price($d_price);
        $this->is_available();
        // returning true - no exception was thrown
        return true;
    }
    protected function is_available()
    {
        if (empty($this->oxvouchers__oxorderid->value)) {
            return true;
        }
        $exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
        $exception->set_message('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
        $exception->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
        throw $exception;
    }
    /**
     * Checks availability about price. Returns error array.
     *
     * @param double $dPrice base article price
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return array
     */
    protected function is_available_price($d_price)
    {
        $o_series = $this->get_serie();
        $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
        if ($o_series->oxvoucherseries__oxminimumvalue->value && $d_price < $o_series->oxvoucherseries__oxminimumvalue->value * $o_cur->rate) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
            $o_ex->set_message('ERROR_MESSAGE_VOUCHER_INCORRECTPRICE');
            $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
            throw $o_ex;
        }
        return true;
    }
    /**
     * Checks if calculation with vouchers of the same series possible. Returns
     * true on success.
     *
     * @param array $aVouchers array of vouchers
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return bool
     */
    protected function is_available_with_same_series($a_vouchers)
    {
        if (is_array($a_vouchers)) {
            $s_id = $this->get_id();
            if (isset($a_vouchers[$s_id])) {
                unset($a_vouchers[$s_id]);
            }
            $o_series = $this->get_serie();
            if (!$o_series->oxvoucherseries__oxallowsameseries->value) {
                foreach ($a_vouchers as $voucher_id => $voucher_nr) {
                    $o_voucher = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher::class);
                    $o_voucher->load($voucher_id);
                    if ($this->oxvouchers__oxvoucherserieid->value == $o_voucher->oxvouchers__oxvoucherserieid->value) {
                        $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
                        $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOTALLOWEDSAMESERIES');
                        $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
                        throw $o_ex;
                    }
                }
            }
        }
        return true;
    }
    /**
     * Checks if calculation with vouchers from the other series possible.
     * Returns true on success.
     *
     * @param array $aVouchers array of vouchers
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return bool
     */
    protected function is_available_with_other_series($a_vouchers)
    {
        if (is_array($a_vouchers) && count($a_vouchers)) {
            $o_series = $this->get_serie();
            $s_ids = implode(',', \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->quote_array(array_keys($a_vouchers)));
            $bl_available = true;
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            if (!$o_series->oxvoucherseries__oxallowotherseries->value) {
                // just search for vouchers with different series
                $s_sql = "select 1 from oxvouchers where oxvouchers.oxid in ({$s_ids}) and ";
                $s_sql .= 'oxvouchers.oxvoucherserieid != :notoxvoucherserieid';
                $bl_available &= !$o_db->get_one($s_sql, ['notoxvoucherserieid' => $this->oxvouchers__oxvoucherserieid->value]);
            } else {
                // search for vouchers with different series and those vouchers do not allow other series
                $s_sql = 'select 1 from oxvouchers left join oxvoucherseries on oxvouchers.oxvoucherserieid=oxvoucherseries.oxid ';
                $s_sql .= "where oxvouchers.oxid in ({$s_ids}) and oxvouchers.oxvoucherserieid != :notoxvoucherserieid ";
                $s_sql .= 'and not oxvoucherseries.oxallowotherseries';
                $bl_available &= !$o_db->get_one($s_sql, ['notoxvoucherserieid' => $this->oxvouchers__oxvoucherserieid->value]);
            }
            if (!$bl_available) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
                $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOTALLOWEDOTHERSERIES');
                $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
                throw $o_ex;
            }
        }
        return true;
    }
    /**
     * Checks if voucher is in valid time period. Returns true on success.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return bool
     */
    protected function is_valid_date()
    {
        $o_series = $this->get_serie();
        $i_time = time();
        // If date is not set will add day before and day after to check if voucher valid today.
        $i_tomorrow = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
        $i_yesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        // Checks if beginning date is set, if not set $iFrom to yesterday so it will be valid.
        $i_from = (int) $o_series->oxvoucherseries__oxbegindate->value ? strtotime((string) $o_series->oxvoucherseries__oxbegindate->value) : $i_yesterday;
        // Checks if end date is set, if no set $iTo to tomorrow so it will be valid.
        $i_to = (int) $o_series->oxvoucherseries__oxenddate->value ? strtotime((string) $o_series->oxvoucherseries__oxenddate->value) : $i_tomorrow;
        if ($i_from < $i_time && $i_to > $i_time) {
            return true;
        }
        $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
        $o_ex->set_message('MESSAGE_COUPON_EXPIRED');
        if ($i_from > $i_time && $i_to > $i_time) {
            $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
        }
        $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
        throw $o_ex;
    }
    /**
     * Checks if voucher is not yet reserved before.
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return bool
     */
    protected function is_not_reserved()
    {
        if ($this->oxvouchers__oxreserved->value < time() - $this->get_voucher_timeout()) {
            return true;
        }
        $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
        $o_ex->set_message('EXCEPTION_VOUCHER_ISRESERVED');
        $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
        throw $o_ex;
    }
    // Checking User Availability
    /**
     * Checks availability for the given user. Returns array with errors.
     *
     * @param object $oUser user object
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return array
     */
    public function check_user_availability($o_user)
    {
        $this->is_available_in_other_order($o_user);
        $this->is_valid_user_group($o_user);
        // returning true if no exception was thrown
        return true;
    }
    /**
     * Checks if user already used vouchers from this series and can he use it again.
     *
     * @param object $oUser user object
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return boolean
     */
    protected function is_available_in_other_order($o_user)
    {
        $o_series = $this->get_serie();
        if (!$o_series->oxvoucherseries__oxallowuseanother->value) {
            $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
            $s_select = 'select count(*) from ' . $this->get_view_name() . ' 
                where oxuserid = :oxuserid and ';
            $s_select .= 'oxvoucherserieid = :oxvoucherserieid and ';
            $s_select .= '((oxorderid is not NULL and oxorderid != "") or (oxdateused is not NULL and oxdateused != 0)) ';
            $params = ['oxuserid' => $o_user->oxuser__oxid->value, 'oxvoucherserieid' => $this->oxvouchers__oxvoucherserieid->value];
            if ($o_db->get_one($s_select, $params)) {
                $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
                $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOTALLOWEDSAMESERIES');
                $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
                throw $o_ex;
            }
        }
        return true;
    }
    /**
     * Checks if user belongs to the same group as the voucher. Returns true on sucess.
     *
     * @param object $oUser user object
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return bool
     */
    protected function is_valid_user_group($o_user)
    {
        $o_voucher_series = $this->get_serie();
        $o_user_groups = $o_voucher_series->set_user_groups();
        if (!$o_user_groups->count()) {
            return true;
        }
        if ($o_user) {
            foreach ($o_user_groups as $o_group) {
                if ($o_user->in_group($o_group->get_id())) {
                    return true;
                }
            }
        }
        $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
        $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOTVALIDUSERGROUP');
        $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
        throw $o_ex;
    }
    /**
     * Returns compact voucher object which is used in oxBasket
     *
     * @return stdClass
     */
    public function get_simple_voucher()
    {
        $o_voucher = new stdClass();
        $o_voucher->s_voucher_id = $this->get_id();
        $o_voucher->s_voucher_nr = null;
        if ($this->oxvouchers__oxvouchernr) {
            $o_voucher->s_voucher_nr = $this->oxvouchers__oxvouchernr->value;
        }
        // R. set in oxBasket : $oVoucher->fVoucherdiscount = \OxidEsales\Eshop\Core\Registry::getLang()->formatCurrency( $this->oxvouchers__oxdiscount->value );
        return $o_voucher;
    }
    /**
     * create oxVoucherSerie object of this voucher
     *
     * @throws \OxidEsales\Eshop\Core\Exception\ObjectException
     *
     * @return \OxidEsales\Eshop\Application\Model\VoucherSerie
     */
    public function get_serie()
    {
        if ($this->_o_serie !== null) {
            return $this->_o_serie;
        }
        $o_serie = ox_new(\Oxid_Esales\Eshop\Application\Model\Voucher_Serie::class);
        if (!$o_serie->load($this->oxvouchers__oxvoucherserieid->value)) {
            throw ox_new(\Oxid_Esales\Eshop\Core\Exception\Object_Exception::class);
        }
        $this->_o_serie = $o_serie;
        return $o_serie;
    }
    /**
     * Returns true if voucher is product specific, otherwise false
     *
     * @return boolean
     */
    protected function is_product_voucher()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_series = $this->get_serie();
        $s_select = 'select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype';
        return (bool) $o_db->get_one($s_select, ['oxdiscountid' => $o_series->get_id(), 'oxtype' => 'oxarticles']);
    }
    /**
     * Returns true if voucher is category specific, otherwise false
     *
     * @return boolean
     */
    protected function is_category_voucher()
    {
        $o_db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $o_series = $this->get_serie();
        $s_select = 'select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype';
        return (bool) $o_db->get_one($s_select, ['oxdiscountid' => $o_series->get_id(), 'oxtype' => 'oxcategories']);
    }
    /**
     * Returns the discount object created from voucher serie data
     *
     * @return object
     */
    protected function get_serie_discount()
    {
        $o_series = $this->get_serie();
        $o_discount = ox_new(\Oxid_Esales\Eshop\Application\Model\Discount::class);
        $o_discount->set_id($o_series->get_id());
        $o_discount->oxdiscount__oxshopid = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxshopid->value);
        $o_discount->oxdiscount__oxactive = new \Oxid_Esales\Eshop\Core\Field(true);
        $o_discount->oxdiscount__oxactivefrom = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxbegindate->value);
        $o_discount->oxdiscount__oxactiveto = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxenddate->value);
        $o_discount->oxdiscount__oxtitle = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxserienr->value);
        $o_discount->oxdiscount__oxamount = new \Oxid_Esales\Eshop\Core\Field(1);
        $o_discount->oxdiscount__oxamountto = new \Oxid_Esales\Eshop\Core\Field(MAX_64BIT_INTEGER);
        $o_discount->oxdiscount__oxprice = new \Oxid_Esales\Eshop\Core\Field(0);
        $o_discount->oxdiscount__oxpriceto = new \Oxid_Esales\Eshop\Core\Field(MAX_64BIT_INTEGER);
        $o_discount->oxdiscount__oxaddsumtype = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxdiscounttype->value == 'percent' ? '%' : 'abs');
        $o_discount->oxdiscount__oxaddsum = new \Oxid_Esales\Eshop\Core\Field($o_series->oxvoucherseries__oxdiscount->value);
        $o_discount->oxdiscount__oxitmartid = new \Oxid_Esales\Eshop\Core\Field();
        $o_discount->oxdiscount__oxitmamount = new \Oxid_Esales\Eshop\Core\Field();
        $o_discount->oxdiscount__oxitmmultiple = new \Oxid_Esales\Eshop\Core\Field();
        return $o_discount;
    }
    /**
     * Returns basket item information array from session or order.
     *
     * @param \OxidEsales\Eshop\Application\Model\Discount $oDiscount discount object
     *
     * @return array
     */
    protected function get_basket_items($o_discount = null)
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        if ($this->oxvouchers__oxorderid->value) {
            return $this->get_order_basket_items($o_discount);
        }
        if ($session->get_basket()) {
            return $this->get_session_basket_items($o_discount);
        }
        return [];
    }
    /**
     * Returns basket item information (id,amount,price) array takig item list from order.
     *
     * @param \OxidEsales\Eshop\Application\Model\Discount $oDiscount discount object
     *
     * @return array
     */
    protected function get_order_basket_items($o_discount = null)
    {
        if (is_null($o_discount)) {
            $o_discount = $this->get_serie_discount();
        }
        $o_order = ox_new(\Oxid_Esales\Eshop\Application\Model\Order::class);
        $o_order->load($this->oxvouchers__oxorderid->value);
        $a_items = [];
        $i_count = 0;
        foreach ($o_order->get_order_articles(true) as $o_order_article) {
            if (!$o_order_article->skip_discounts() && $o_discount->is_for_basket_item($o_order_article)) {
                $a_items[$i_count] = ['oxid' => $o_order_article->get_product_id(), 'price' => $o_order_article->oxorderarticles__oxbprice->value, 'discount' => $o_discount->get_abs_value($o_order_article->oxorderarticles__oxbprice->value), 'amount' => $o_order_article->oxorderarticles__oxamount->value];
                $i_count++;
            }
        }
        return $a_items;
    }
    /**
     * Returns basket item information (id,amount,price) array taking item list from session.
     *
     * @param \OxidEsales\Eshop\Application\Model\Discount $oDiscount discount object
     *
     * @return array
     */
    protected function get_session_basket_items($o_discount = null)
    {
        if (is_null($o_discount)) {
            $o_discount = $this->get_serie_discount();
        }
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $o_basket = $session->get_basket();
        $a_items = [];
        $i_count = 0;
        foreach ($o_basket->get_contents() as $o_basket_item) {
            if (!$o_basket_item->is_discount_article() && ($o_article = $o_basket_item->get_article()) && !$o_article->skip_discounts() && $o_discount->is_for_basket_item($o_article)) {
                $a_items[$i_count] = ['oxid' => $o_article->get_id(), 'price' => $o_article->get_basket_price($o_basket_item->get_amount(), $o_basket_item->get_sel_list(), $o_basket)->get_price(), 'discount' => $o_discount->get_abs_value($o_article->get_basket_price($o_basket_item->get_amount(), $o_basket_item->get_sel_list(), $o_basket)->get_price()), 'amount' => $o_basket_item->get_amount()];
                $i_count++;
            }
        }
        return $a_items;
    }
    /**
     * Returns the discount value used.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return double
     */
    protected function get_generic_discount_value($d_price)
    {
        $o_series = $this->get_serie();
        if ($o_series->oxvoucherseries__oxdiscounttype->value == 'absolute') {
            $o_cur = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_act_shop_currency_object();
            $d_discount = $o_series->oxvoucherseries__oxdiscount->value * $o_cur->rate;
        } else {
            $d_discount = $o_series->oxvoucherseries__oxdiscount->value / 100 * $d_price;
        }
        if ($d_discount > $d_price) {
            return $d_price;
        }
        return $d_discount;
    }
    /**
     * Return discount value
     *
     * @return double
     */
    public function get_discount()
    {
        $o_series = $this->get_serie();
        return $o_series->oxvoucherseries__oxdiscount->value;
    }
    /**
     * Return discount type
     *
     * @return string
     */
    public function get_discount_type()
    {
        $o_series = $this->get_serie();
        return $o_series->oxvoucherseries__oxdiscounttype->value;
    }
    /**
     * Returns the discount value used, if voucher is aplied only for specific products.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return double
     */
    protected function get_product_discount_value($d_price)
    {
        $o_discount = $this->get_serie_discount();
        $a_basket_items = $this->get_basket_items($o_discount);
        // Basket Item Count and isAdmin check (unble to access property $oOrder->getOrderBasket()->_blSkipVouchersAvailabilityChecking)
        if (!count($a_basket_items) && !$this->is_admin()) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
            $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
            $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
            throw $o_ex;
        }
        $o_series = $this->get_serie();
        $o_voucher_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $o_discount_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $o_product_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $o_product_total = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        // Is the voucher discount applied to at least one basket item
        $bl_discount_applied = false;
        foreach ($a_basket_items as $a_basket_item) {
            // If discount was already applied for the voucher to at least one basket items, then break
            if ($bl_discount_applied and !empty($o_series->oxvoucherseries__oxcalculateonce->value)) {
                break;
            }
            $o_discount_price->set_price($a_basket_item['discount']);
            $o_product_price->set_price($a_basket_item['price']);
            // Individual voucher is not multiplied by article amount
            if (!$o_series->oxvoucherseries__oxcalculateonce->value) {
                $o_discount_price->multiply($a_basket_item['amount']);
                $o_product_price->multiply($a_basket_item['amount']);
            }
            $o_voucher_price->add($o_discount_price->get_brutto_price());
            $o_product_total->add($o_product_price->get_brutto_price());
            if (!empty($a_basket_item['discount'])) {
                $bl_discount_applied = true;
            }
        }
        $d_voucher = $o_voucher_price->get_brutto_price();
        $d_product = $o_product_total->get_brutto_price();
        if ($d_voucher > $d_product) {
            return $d_product;
        }
        return $d_voucher;
    }
    /**
     * Returns the discount value used, if voucher is applied only for specific categories.
     *
     * @param double $dPrice price to calculate discount on it
     *
     * @throws \OxidEsales\Eshop\Core\Exception\VoucherException
     *
     * @return double
     */
    protected function get_category_discount_value($d_price)
    {
        $o_discount = $this->get_serie_discount();
        $a_basket_items = $this->get_basket_items($o_discount);
        // Basket Item Count and isAdmin check (unable to access property $oOrder->getOrderBasket()->_blSkipVouchersAvailabilityChecking)
        if (!count($a_basket_items) && !$this->is_admin()) {
            $o_ex = ox_new(\Oxid_Esales\Eshop\Core\Exception\Voucher_Exception::class);
            $o_ex->set_message('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
            $o_ex->set_voucher_nr($this->oxvouchers__oxvouchernr->value);
            throw $o_ex;
        }
        $o_product_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        $o_product_total = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
        foreach ($a_basket_items as $a_basket_item) {
            $o_product_price->set_price($a_basket_item['price']);
            $o_product_price->multiply($a_basket_item['amount']);
            $o_product_total->add($o_product_price->get_brutto_price());
        }
        $d_product = $o_product_total->get_brutto_price();
        $d_voucher = $o_discount->get_abs_value($d_product);
        return $d_voucher > $d_product ? $d_product : $d_voucher;
    }
    /**
     * Extra getter to guarantee compatibility with templates
     *
     * @param string $sName name of variable to get
     *
     * @return string
     */
    public function __get($s_name)
    {
        return match ($s_name) {
            'sVoucherId' => $this->get_id(),
            'sVoucherNr' => $this->oxvouchers__oxvouchernr,
            'fVoucherdiscount' => $this->oxvouchers__oxdiscount,
            default => parent::__get($s_name),
        };
    }
    /**
     * Returns a configured value for voucher timeouts or a default
     * of 3 hours if not configured
     *
     * @return integer Seconds a voucher can stay in status reserved
     */
    protected function get_voucher_timeout()
    {
        return (int) \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('iVoucherTimeout') ?: 3 * 3600;
    }
}